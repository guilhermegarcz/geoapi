<?php

namespace App\Controller\Api;

use IP2Location\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class geoController
 * @package App\Controller\Api
 * @Route("/api/v1")
 */
class geoController extends AbstractController
{
    const STATUS_SUCCESS = "STATUS_SUCCESS";
    const STATUS_FAILED = "STATUS_FAILED";

    const REASON_NO_DATA = "NO_DATA";
    const REASON_BAD_IP = "BAD_IP";
    /**
     * @var Database
     */
    protected $ip2locationV4;

    public function __construct(string $ip2location_path)
    {
        try {
            $this->ip2locationV4 = new Database(sprintf('%s/IP2LOCATION-LITE-DB11.BIN', $ip2location_path), Database::MEMORY_CACHE);
        } catch (\Exception $e) {
            throw new Exception(sprintf('%s, Failed to load DB', $e->getMessage()));
        }
    }

    /**
     * @Route("/geo", name="api_geo")
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $ip = $request->get('ip');
        if(is_null($ip) || empty($ip) || !(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))){
            return new JsonResponse([
                'status' => self::STATUS_FAILED,
                'reason' => self::REASON_BAD_IP,
                'data' => []
            ]);
        }

        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            $data = $this->ip2locationV4->lookup($ip);

        if(!$data)
        {
            return new JsonResponse([
                'status' => self::STATUS_FAILED,
                'reason' => self::REASON_NO_DATA,
                'data' => []
            ]);
        }
        return new JsonResponse([
            'status' => self::STATUS_SUCCESS,
            'data' => [
                'ipNumber' => $data['ipNumber'],
                'ipVersion' => $data['ipVersion'],
                'ipAddress' => $data['ipAddress'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'countryName' => $data['countryName'],
                'countryCode' => $data['countryCode'],
                'timeZone' => $data['timeZone'],
                'zipCode' => $data['zipCode'],
                'cityName' => $data['cityName'],
                'regionName' => $data['regionName'],
            ]
        ]);
    }
}
