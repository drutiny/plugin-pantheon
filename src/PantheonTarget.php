<?php

namespace Drutiny\Pantheon;

use Drutiny\Target\DrushTarget;
use Drutiny\Target\InvalidTargetException;
use Drutiny\Target\TargetSourceInterface;
use Drutiny\Target\TargetInterface;

/**
 * Pantheon Target
 */
class PantheonTarget extends DrushTarget implements TargetSourceInterface
{
    /**
     * Parse target data.
     */
    public function parse(string $alias, ?string $uri = NULL): TargetInterface
    {
        list($site, $env) = explode('.', $alias, 2);

        $this['drush.alias'] = '@pantheon.'.$alias;

        parent::parse($this['drush.alias']);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTargets():array
    {
      $sites = $this['service.local']->run('terminus site:list --format=json', function ($output) {
        $sites = json_decode($output, true);
        foreach ($sites as &$site) {
          $site['envs'] = $this['service.local']->run(sprintf('terminus env:list %s --format=json', $site['id']), function ($envo) {
              return json_decode($envo, true);
          });
        }
        return $sites;
      });

      $targets = [];
      foreach ($sites as $site) {
        foreach ($site['envs'] as $env) {
          $targets[] = [
            'id' => $site['name'].'.'.$env['id'],
            'uri' => $env['domain'],
            'name' => $site['name'].': '.$env['id']
          ];
        }
      }
      return $targets;
    }
}
