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


    public function logs_r1(Request $request, Response $response, array $args = []): Response
    {
        $rp = $this->utils->getQueryParams($request) ?? [];
        $qs = (new Sql)->q['logs'];
        $qs = (new \Glued\Lib\QueryBuilder())->select($qs);
        $qs = $this->utils->mysqlQueryFromRequest($qs, $rp, 'c_data');
        $r = $this->mysqli->execute_query($qs,array_values($rp));
        $res['status'] = 'ok';
        foreach ($r as $i) {
            $res['data'][] = $i;
        }
        return $response->withJson($res);
    }

    public function services_r1(Request $request, Response $response, array $args = []): Response
    {
        $routes = array_filter($this->settings['routes'], function ($key) {
            return strpos($key, 'be_if_svc') === 0;
        }, ARRAY_FILTER_USE_KEY);
        foreach ($routes as $route) {
            $provides = $route['provides'] ?? '';
            if ($provides === 'docs') {
                $ret['label'] = $route['label'];
                $ret['dscr'] = $route['dscr'];
                $ret['url'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . $route['path'];
                $svcs[] = $ret;
            }
        }
        $payload['data'] = $svcs;
        $payload['help']['get'] = 'List all IF v1 compliant service providers (A `"provides": "docs"` route is present).';
        $payload['help']['post'] = 'Post a json according to `service docs` to this interface as a request body to add a service coupler.';
        $routes = array_filter($this->settings['routes'], function ($key) {
            return strpos($key, 'be_if_') === 0 && strpos($key, 'be_if_svc') !== 0;
        }, ARRAY_FILTER_USE_KEY);
        foreach ($routes as $route) {
            $f['txt'] = trim($route['label']. " / " . $route['dscr']);
            $f['uri'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . $route['path'];
            $payload['links'][] = $f;
        }
        $payload['status'] = 'pl';
        return $response->withJson($payload);
    }

    public function services_c1(Request $request, Response $response, array $args = []): Response
    {
        $payload = (array) $request->getBody()->getContents();
        $payload = json_decode($payload[0], true);

        $svc_uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $data = $payload['svc'];
        $data = [ 'c_uuid' => $svc_uuid, 'c_data' => json_encode($data) ];
        $x = $this->db->rawQuery('INSERT INTO `t_if__services` ( `c_uuid`, `c_data` ) VALUES ( UUID_TO_BIN(?, true), ? )', $data);

        foreach ($payload['act'] as $a) {
            $act_uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $data = [ 'c_svc_uuid' => $svc_uuid, 'c_uuid' => $act_uuid, 'c_data' => json_encode($a) ];
            $this->db->rawQuery('INSERT INTO `t_if__actions` ( `c_svc_uuid`, `c_uuid`, `c_data` ) VALUES ( UUID_TO_BIN(?, true), UUID_TO_BIN(?, true), ? )', $data);
        }

        return $response->withJson($payload);
    }



    public function queue_r1(Request $request, Response $response, array $args = []): Response
    {
        $rp = $this->utils->getQueryParams($request) ?? [];
        $override = [ [ 'next_in', '<', '0' ], [ 'row_num', '=', '1' ] ];
        $qs = (new Sql)->q['queue'];
        $qs = (new \Glued\Lib\QueryBuilder())->select($qs);
        $qs = $this->utils->mysqlQueryFromRequest($qs, $rp, 'svc_data', override: $override);
        $r = $this->mysqli->execute_query($qs,array_values($rp));
        $res['status'] = 'ok';
        foreach ($r as $i) {
            $i['run'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . '/api/if/v1/svc/' . $i['svc_type'] . '/act/' . $i['act_uuid'];
            $i['log'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . '/api/if/v1/logs/' . $i['act_uuid'];
            $res['data'][] = $i;
        }
        return $response->withJson($res);
    }

    public function reg_r1(Request $request, Response $response, array $args = []): Response
    {
        $rp = $this->utils->getQueryParams($request) ?? [];
        $override = [ [ 'row_num', '=', '1' ] ];
        $qs = (new Sql)->q['queue'];
        $qs = (new \Glued\Lib\QueryBuilder())->select($qs);
        $qs = $this->utils->mysqlQueryFromRequest($qs, $rp, 'svc_data', override: $override);
        $r = $this->mysqli->execute_query($qs,array_values($rp));
        $res['status'] = 'ok';
        foreach ($r as $i) {
            $i['run'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . '/api/if/v1/svc/' . $i['svc_type'] . '/act/' . $i['act_uuid'];
            $i['log'] = $this->settings['glued']['protocol'] . $this->settings['glued']['hostname'] . '/api/if/v1/logs/' . $i['act_uuid'];
            $res['data'][] = $i;
        }
        return $response->withJson($res);
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