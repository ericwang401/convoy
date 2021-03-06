<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\Nodes\Information\ResourceService as InformationResourceService;
use App\Services\ProxmoxService;
use Exception;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class ResourceService extends ProxmoxService
{
    private InformationResourceService $resourceService;

    public function __construct()
    {
        $this->resourceService = new InformationResourceService;
    }

    public function getResources()
    {
        $allResources = $this->resourceService->setServer($this->server)->getResourceList();

        $resources = array_search('qemu/' . $this->server->vmid, array_column($allResources, 'id'));

        return $allResources[$resources];
    }

    // uses the /pending endpoint
    public function getConfig()
    {
        return $this->removeDataProperty($this->instance()->pending()->get());
    }

    public function setMemory(int $bytes)
    {
        return $this->instance()->config()->put(['memory' => $bytes]);
    }

    public function setCores(int $cores)
    {
        return $this->instance()->config()->put(['cores' => $cores]);
    }

    public function increaseDisk(int $bytes, string $disk)
    {
        return $this->instance()->resize()->put(['disk' => $disk, 'size' => '+' . ($bytes / 1024 / 1024 / 1024) . 'G']);
    }

    public function createDisk(int $bytes, string $disk, string $format = 'qcow2')
    {
        return $this->instance()->config()->post([$disk => 'local:' . ($bytes / 1024 / 1024 / 1024) . ',format=' . $format]);
    }

    public function getBootOrder()
    {
        $configs = $this->getConfig();
        $rawBootOrder = $configs[array_search('boot', array_column($configs, 'key'))];

        $splitEquals = explode('=', $rawBootOrder['value']);

        if (array_key_exists(1, $splitEquals)) {
            return ['raw' => $rawBootOrder['value'], 'parsed' => explode(',', $splitEquals[1])];
        }

        return ['raw' => $rawBootOrder['value'], 'parsed' => []];
    }

    public function setBootOrder(string $order)
    {
        return $this->instance()->config()->put(['boot' => $order]);
    }

    public function parseDisk(array $disk): array
    {
        $parsedDisk = [
            'disk' => $disk['key'],
            'size' => '',
            'pending' =>  array_key_exists('pending', $disk) ? true : false,
        ];

        try {
            $splitSlashes = explode('/', array_key_exists('value', $disk) ? $disk['value'] : $disk['pending']);
            $splitCommas = explode(',', $splitSlashes[count($splitSlashes) - 1]);
            $splitEquals = explode('=', $splitCommas[count($splitCommas) - 1]);

            $parsedDisk['size'] = $splitEquals[1];

            return $parsedDisk;
        } catch (Exception $e) {
            return $parsedDisk;
        }
    }


    public function startsWith($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    // $showNonprimaryDisks if true, will show disks with NULL sizes and Cloudinit disks
    public function getDisks(bool $showNonprimaryDisks = false)
    {

        $configs = $this->getConfig();
        $disks = [];

        $diskTypes = [
            'scsi',
            'sata',
            'virtio',
            'ide',
        ];

        // not all config values are for disks
        foreach ($configs as $config) {
            // this checks if the config value is a disk
            foreach ($diskTypes as $diskType) {
                if ($this->startsWith($config['key'], $diskType)) {
                    $parsedDisk = $this->parseDisk($config);

                    if ($showNonprimaryDisks) {
                        // show NULL disks and Cloudinit disks
                        array_push($disks, $parsedDisk);
                    } else {
                        // no matter what, it'll always return the value
                        $standardizedValue =  array_key_exists('pending', $config) ? $config['pending'] : $config['value'];

                        if ($parsedDisk['size'] !== null && !str_contains($standardizedValue, 'cloudinit') && !str_contains($config['key'], '-') && $parsedDisk['disk'] !== 'scsihw') {
                            array_push($disks, $parsedDisk);
                        }
                    }
                }
            }
        }

        return $disks;
    }
}
