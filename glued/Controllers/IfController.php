<?php

declare(strict_types=1);

namespace Glued\Controllers;

use Glued\Classes\Sql;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IfController extends AbstractController
{

    /**
     * Returns a health status response.
     * @param  Request  $request
     * @param  Response $response
     * @param  array    $args
     * @return Response Json result set.
     */
    public function health(Request $request, Response $response, array $args = []): Response {
        $data = [
            'timestamp' => microtime(),
            'status' => 'ok',
            'params' => $request->getQueryParams(),
            'service' => basename(__ROOT__),
            'provided-for' => $_SERVER['X-GLUED-AUTH-UUID'] ?? 'anon'
        ];
        return $response->withJson($data);
    }

    private function getServices() {
        $filteredRoutes = array_filter($this->settings['routes'], function ($route) {
            return isset($route['service']) && strpos($route['service'], 'if/') === 0;
        });
        $uniqueServices = array_unique(array_column($filteredRoutes, 'service'));
        $uniqueServices = array_values(array_map(function ($service) {
            return str_replace('if/', '', $service);
        }, $uniqueServices));
        return $uniqueServices;
    }
    public function runs_r1(Request $request, Response $response, array $args = []): Response
    {
        $rp = $this->utils->getQueryParams($request) ?? [];
        $qs = (new \Glued\Lib\IfSql())->q['json:runs:all'];
        $qs = (new \Glued\Lib\QueryBuilder())->select($qs);
        $qs = $this->utils->mysqlQueryFromRequest($qs, $rp, 'c_data');
        $r = $this->mysqli->execute_query($qs,array_values($rp));
        $res['status'] = 'ok';
        foreach ($r as $i) {
            $res['data'][] = $i;
        }
        return $response->withJson($res);
    }

    public function hello_r1(Request $request, Response $response, array $args = []): Response
    {
        $payload['help']['get'] = 'List all IF v1 compliant service providers (A `"provides": "docs"` route is present).';
        $payload['help']['post'] = 'Post a json according to `service docs` to this interface as a request body to add a service coupler.';
        $routes = array_filter($this->settings['routes'], function ($key) {
            // return strpos($key, 'be_if_') === 0 &&
            return strpos($key, 'be_if_svc') === 0;
        }, ARRAY_FILTER_USE_KEY);
        foreach ($routes as $route) {
            $f['txt'] = trim($route['label']. " / " . $route['dscr']);
            $f['uri'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . $route['path'];
            $payload['links'][] = $f;
        }
        $payload['status'] = 'Ok';
        return $response->withJson($payload);
    }

    public function services_r1(Request $request, Response $response, array $args = []): Response
    {
        $base = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'];
        $svcs = [];
        foreach ($this->getServices() as $key => $value) {
            $svcs[$key]['name'] = $value;
            $svcs[$key]['links'] = "{$base}/{$this->settings['routes']['be_if_deployments_v1']['path']}/$value/deployments";
        }
        $payload['status'] = 'Ok';
        $payload['data'] = $svcs;
        return $response->withJson($payload);
    }

    public function deployments_c1(Request $request, Response $response, array $args = []): Response
    {
        $params = $request->getQueryParams();
        $contentTypeHeader = $request->getHeaderLine('Content-Type') ?? '';
        if ($contentTypeHeader !== 'application/json') { throw new \Exception('Invalid Content-Type. Please set `Content-Type: application/json', 400); }
        $payload = $request->getParsedBody();
        foreach ($payload as $item) {
            $svc_uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $svc_data = json_encode($item['deployment']);
            $res = $this->mysqli->execute_query('SELECT bin_to_uuid(`c_uuid`,true) as svc_uuid FROM `t_if__deployments` WHERE JSON_CONTAINS(c_data, ?, "$")', [$svc_data]);
            foreach ($res as $r) { $svc_uuid = $r['svc_uuid']; break; }
            $this->mysqli->execute_query("INSERT INTO `t_if__deployments` ( `c_uuid`, `c_data` ) VALUES ( UUID_TO_BIN(?, true), ? ) ON DUPLICATE KEY UPDATE `c_data` = values(`c_data`)", [$svc_uuid, $svc_data]);

            foreach ($item['actions'] as $a) {
                $act_uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
                $data = ['c_deployment_uuid' => $svc_uuid, 'c_uuid' => $act_uuid, 'c_data' => json_encode($a)];
                $this->db->rawQuery('INSERT INTO `t_if__actions` ( `c_deployment_uuid`, `c_uuid`, `c_data` ) VALUES ( UUID_TO_BIN(?, true), UUID_TO_BIN(?, true), ? ) ON DUPLICATE KEY UPDATE `c_data` = VALUES(`c_data`)', $data);
            }
        }
        return $response->withJson($payload);
    }


    public function queue_r1(Request $request, Response $response, array $args = []): Response
    {
        $rp = $this->utils->getQueryParams($request) ?? [];
        $override = [ [ 'next_in', '<', '0' ], [ 'row_num', '=', '1' ] ];
        $qs = (new \Glued\Lib\IfSql())->q['json:runs:latest'];;
        $qs = (new \Glued\Lib\QueryBuilder())->select($qs);
        $qs = $this->utils->mysqlQueryFromRequest($qs, $rp, 'svc_data', override: $override);
        $r = $this->mysqli->execute_query($qs,array_values($rp));
        $res['status'] = 'ok';
        foreach ($r as $i) {
            $i['run'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . '/api/if/v1/svc/' . $i['svc_type'] . '/act/' . $i['act_uuid'];
            $i['run'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . '/api/if/v1/runs/' . $i['act_uuid'];
            $res['data'][] = $i;
        }
        return $response->withJson($res);
    }

    public function deployments_r1(Request $request, Response $response, array $args = []): Response
    {

        $data = [];
        $rp = $this->utils->getQueryParams($request) ?? [];
        $qs = (new \Glued\Lib\IfSql())->q['json:runs:latest'];
        if ($args['svc'] ?? false) { $data[] = $args['svc']; $qs.= " and subquery.service = ?"; }
        $data = array_values($rp) + $data;
        $res = $this->mysqli->execute_query($qs, $data);
        foreach ($res as $row) {
            $data = json_decode($row['json_result'], true); break; }
        $fin['status'] = 'ok';
        $base = "{$this->settings['glued']['protocol']}{$this->settings['glued']['hostname']}/api/if";
        foreach ($data as $k => &$i) {
            $data[$k]['links']['start'] = "{$base}/svc/{$i['service']}/v1/act/{$i['action']['uuid']}";
            //$i['run'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . '/api/if/v1/runs/' . $i['act_uuid'];
        }
        $fin['data'] = $data;

            //$res['data'][] = $i;
        return $response->withJson($fin);
    }

    public function stats_r1(Request $request, Response $response, array $args = []): Response
    {
        $res = ['status' => 'ok', 'message' => 'This endpoint is under development.'];
        return $response->withJson($res);
    }


}


// {"svc":{"type":"Caretag","name":"NEMCB Prod","host":"https:\/\/caretag-api.nemocnice.local","note": "Production environment (pavel.stratil-jun@fenix.cz)","freq":3600,"auth":{"UserName":"pavel.stratil-jun@fenix.cz","Password":"Administrator1!"}},"act":[{"type":"Assets","freq":3600},{"type":"AssetDefinition","freq":36000}]}
// toalety - wc
//