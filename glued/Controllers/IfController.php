<?php

declare(strict_types=1);

namespace Glued\Controllers;

use Glued\Classes\Sql;
use mysql_xdevapi\Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \PDO;
use Glued\Lib\IfUtils;

class IfController extends AbstractController
{


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getOpenapi(Request $request, Response $response, array $args = []): Response
    {
        // Directory to look for paths
        $path = "{$this->settings['glued']['datapath']}/{$this->settings['glued']['uservice']}/cache" ;
        $filesWhitelist = ["openapi.json", "openapi.yaml", "openapi.yml"]; // Potential file names

        foreach ($filesWhitelist as $file) {
            $fullPath = rtrim($path, '/') . '/' . $file;
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                $response->getBody()->write($content);
                $contentType = 'application/json';
                if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'yaml' || pathinfo($fullPath, PATHINFO_EXTENSION) === 'yml') { $contentType = 'application/x-yaml'; }
                return $response->withHeader('Content-Type', $contentType);
            }
        }
        throw new \Exception("OpenAPI specification not found", 404);
    }


    public function getHealth(Request $request, Response $response, array $args = []): Response {
        try {
            $check['service'] = basename(__ROOT__);
            $check['timestamp'] = microtime();
            $check['healthy'] = true;
            $check['status']['postgres'] = $this->pg->query("select true as test")->fetch()['test'] ?? false;
            $check['status']['auth'] = $_SERVER['X-GLUED-AUTH-UUID'] ?? 'anonymous';
        } catch (Exception $e) {
            $check['healthy'] = false;
            return $response->withJson($check);
        }
        return $response->withJson($check);
        /*
        $db = new \Glued\Lib\Sql($this->pg, 'your_table');
        $db->where('uuid', '=', '8f337987-9b3e-4285-a0f4-4bd70101bd07');
        //$db->createBatch([['prca' => 'puc'],['prcb' => 'puc'],['prca' => 'pucaaaaaaaaaaaaaa', "uuid" => "92955e5d-76d6-4b8b-8376-2d1be820ee7d"]], true);
        //$db->stmt->debugDumpParams();
        $db->update("92955e5d-76d6-4b8b-8376-2d1be820ee7d", ['prca' => '11113', "uuid" => "92955e5d-76d6-4b8b-8376-2d1be820ee7d"]);
        //$data['d'] = $db->debug();
        $db->delete('92955e5d-76d6-4b8b-8376-2d1be820ee7d');
        //echo $db->stmt->rowCount();
        $data['r'] = $db->getAll();
        //$data = $db->get('8f337987-9b3e-4285-a0f4-4bd70101bd07');
        //$data = $db->getAll();
        //return $response;*/
    }

    public function getServices(Request $request, Response $response, array $args = []): Response {
        $filteredRoutes = array_filter($this->settings['routes'], function ($route) {
            return isset($route['service']) && strpos($route['service'], 'if/') === 0;
        });
        $uniqueServices = array_unique(array_column($filteredRoutes, 'service'));
        $uniqueServices = array_values(array_map(function ($service) {
            $svc = str_replace('if/', '', $service);
            return [
                'service' => $svc,
                'deployments' => $this->settings['glued']['baseuri'] . $this->settings['routes']['be_if_deployments']['pattern'] . "/name/" . $svc
            ];
        }, $uniqueServices));
        return $response->withJson($uniqueServices);
    }

    public function getDeployments(Request $request, Response $response, array $args = []): Response
    {
        $db = new \Glued\Lib\Sql($this->pg, 'if__deployments');
        $db->selectModifier = "jsonb_build_object('uri', concat('{$this->settings['glued']['baseuri']}/{$this->settings['routes']['be_if_svc_s4s']['pattern']}v1/', doc->>'uuid'), 'nonce', nonce, 'created_at', created_at, 'updated_at', updated_at) || ";
        $data = $db->getAll();
        //$db->stmt->debugDumpParams();
        return $response->withJson($data);
    }

}


/*
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

*/
