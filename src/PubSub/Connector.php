<?php

namespace Myli\PlainJobs\PubSub;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Str;

class Connector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $gcp_config = $this->transformConfig($config);

        return new Queue(
            new \Google\Cloud\PubSub\PubSubClient($gcp_config),
            $config['queue'] ?? null
        );
    }

    /**
     * Transform the config to key => value array.
     *
     * @param  array $config
     *
     * @return array
     */
    protected function transformConfig($config)
    {
        return array_reduce(array_map([$this, 'transformConfigKeys'], $config, array_keys($config)), function ($carry, $item) {
            $carry[$item[0]] = $item[1];

            return $carry;
        }, []);
    }

    /**
     * Transform the keys of config to camelCase.
     *
     * @param  string $item
     * @param  string $key
     *
     * @return array
     */
    protected function transformConfigKeys($item, $key)
    {
        return [Str::camel($key), $item];
    }
}
