<?php

declare(strict_types=1);

namespace Glued\Controllers;

use Glued\Lib\IngestAppend;
use Glued\Lib\Sql;
use Glued\Lib\TsSql;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Glued\Lib\Controllers\AbstractService;
use Selective\Transformer\ArrayTransformer;

class IfController extends AbstractService
{


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /*
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
            //return $response;*
    }*/

    public function getServices(Request $request, Response $response, array $args = []): Response {
        $filteredRoutes = array_filter($this->settings['routes'], function ($route) {
            return isset($route['service']) && strpos($route['service'], 'if/') === 0;
        });
        $uniqueServices = array_unique(array_column($filteredRoutes, 'service'));
        $uniqueServices = array_values(array_map(function ($service) {
            $svc = str_replace('if/', '', $service);
            return [
                'service' => $svc,
                'deployments' => $this->settings['glued']['baseuri'] . $this->settings['routes']['be_if_deployments']['pattern'] . "?service=" . $svc
            ];
        }, $uniqueServices));
        return $response->withJson($uniqueServices);
    }

    public function getDeployments(Request $request, Response $response, array $args = []): Response
    {
        $db = new Sql($this->pg, 'if__deployments');
        $qp = $request->getQueryParams();
        $filters = ['uuid', 'service'];
        foreach ($filters as $filter) {
            if (!empty($qp[$filter])) { $db->where($filter, '=', $qp[$filter]); }
        }
        $db->selectModifier = "jsonb_build_object('uri', concat('{$this->settings['glued']['baseuri']}{$this->settings['routes']['be_if']['pattern']}svc/', doc->>'service', '/v1/', doc->>'uuid'), 'nonce', nonce, 'created_at', created_at, 'updated_at', updated_at) || ";
        $data = $db->getAll();
        return $response->withJson($data);
    }

    public function getDeployment(Request $request, Response $response, array $args = []): Response
    {
        if (empty($args['uuid'])) { throw new \Exception('No uuid specified', 404); }
        $db = new Sql($this->pg, 'if__deployments');
        $db->selectModifier = "jsonb_build_object('uri', concat('{$this->settings['glued']['baseuri']}{$this->settings['routes']['be_if']['pattern']}svc/', doc->>'service', '/v1/', doc->>'uuid'), 'nonce', nonce, 'created_at', created_at, 'updated_at', updated_at) || ";
        $data = $db->get($args['uuid']);
        return $response->withJson($data);
    }


    public function getIngests(Request $request, Response $response, array $args = []): Response
    {
        $db = new IngestAppend($this->pg, 'if__ingest_log');
        //$db->limit = 10;
        $docs = $db->getAll();
        return $response->withJson($docs);
    }

    public function postIngests(Request $request, Response $response, array $args = []): Response
    {
        if (($request->getHeader('Content-Type')[0] ?? '') != 'application/json') { throw new \Exception('Content-Type header missing or not set to `application/json`.', 400); };
        $doc = $this->getValidatedRequestBody($request, $response);
        $db = new IngestAppend($this->pg, 'if__ingest_log');
        $doc = $db->log($doc, '');
        return $response->withJson($doc);
    }


    /*
    public function getActions(Request $request, Response $response, array $args = []): Response
    {
        $db = new Sql($this->pg, 'if__actions');
        $qp = $request->getQueryParams();
        $filters = ['svc_name', 'svc_version', 'svc_method', 'svc_deployment'];
        foreach ($filters as $filter) {
            if (!empty($qp[$filter])) { $db->where($filter, '=', $qp[$filter]); }
        }
        $db->selectModifier = "jsonb_build_object('uri', concat('{$this->settings['glued']['baseuri']}{$this->settings['routes']['be_if']['pattern']}svc/', doc->>'svc_name', '/v1/', doc->>'uuid'), 'nonce', nonce, 'created_at', created_at, 'updated_at', updated_at) || ";
        $data = $db->getAll();
        //$db->stmt->debugDumpParams();
        return $response->withJson($data);
    }
*/
    /**
     * Abstract method to get data from upstream (must be implemented in child classes).
     * @return array
     *
     * @example Example implementation:
     * ```php
     *   $data = logic_getting_upstream_data();
     *   return $data;
     * ```
     */
    protected function getUpstream(): array
    {
        throw new \Exception("Method getUpstream() not implemented");
    }

    /**
     * Abstract method to save raw and transformed upstream data (must be implemented in child classes).
     *
     * @param array $upstreamData The upstream data to save.
     * @return bool True if saving succeeded, false otherwise.
     *
     * @example Example implementation:
     *   ```php
     *     $xf = new ArrayTransformer();
     *     $xf->registerFilter("prefix", function ($value) { return "{$this->uuids['if:deployment']}/$value"; });
     *     $xf->map("uuid", "uuid")
     *        ->map("key1", "key2");
     *     $tsdb = new TsSql($this->pg, "some_table_tsdb", "external_unique_id");
     *     return $tsdb->CommonCreateBatch($upstreamData, $xf);
     *   ```
     */
    protected function saveUpstream(array $upstreamData): bool
    {
        throw new \Exception("Method getUpstream() not implemented");
    }
    /**
     * Syncs upstream data if the last sync occurred more than the specified TTL (time-to-live).
     *
     * This method checks the cache for the last sync timestamp using the provided cache key.
     * If the last sync occurred more than `syncedTtl` seconds ago, it will attempt to sync upstream data.
     * After a successful sync, the cache is updated with the current timestamp.
     * The method returns an HTTP-like status code based on the result of the sync.
     *
     * @param string $cacheKey      The key used to retrieve and store the sync timestamp in the cache.
     * @param int    $syncedTtl     The time-to-live (in seconds) for considering the upstream sync as fresh. Defaults to 60 seconds.
     * @param int    $staleTreshold The threshold (in seconds) for considering the upstream sync as stale. Defaults to 3600 seconds (1 hour).
     *
     * @return int Returns an HTTP-like status code:
     *   - 200 if the upstream sync was successful.
     *   - 203 if the sync was not performed (data from cache is returned).
     *   - 502 if the sync failed and the last successful sync is older than the stale threshold.
     */
    public function syncUpstream(string $cacheKey, int $syncedTtl = 60, int $staleTreshold = 3600): int
    {
        $cacheTtl      = 86400; // 24 hours in seconds
        $syncedResult  = null;
        $now           = time();

        // Check if the last sync was more than 60 seconds ago, sync if yes
        $syncedAt = $this->memcache->get($cacheKey, 0);
        if (($now - $syncedAt) > $syncedTtl) {
            $syncedResult = $this->saveUpstream($this->getUpstream());
            if ($syncedResult === false) { $this->logger->error('Upstream sync failed.', ['cacheKey' => $cacheKey]); }
            else { $this->memcache->set($cacheKey, $now, $cacheTtl); }
        }
        $status = ($syncedResult === false)
            ? (($now - $syncedAt) > $staleTreshold ? 502 : 203)  // 502 if sync failed for >1 hour, else 203
            : ($syncedResult === null ? 203 : 200);  // 203 if cached, 200 if sync successful
        return $status;
    }


}

