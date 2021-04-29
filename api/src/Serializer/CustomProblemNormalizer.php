<?php
# src/App/Serializer/CustomProblemNormalizer.php
namespace App\Serializer;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\Request;

class CustomProblemNormalizer implements NormalizerInterface
{
    protected $mapping = [
        'entry_points' => ['code'=> 1040, 'message' => 'Entry point not found.'],
        'estimate' => ['code'=> 404, 'message' => 'Estimate not found.'],
        'labels' => ['code'=> 1205, 'message' => 'Label not found.'],
        'manifests' => ['code'=> 1275, 'message' => 'Manifest not found.'],
        'overpacks' => ['code'=> 1250, 'message' => 'Overpack not found.'],
        'shipments'=> ['code'=> 1210, 'message' => 'Shipment not found.'],
    ];

    public function normalize($exception, string $format = null, array $context = [])
    {
        $code = $exception->getStatusCode();
        $message =  $exception->getMessage();

        switch ($code) {
            case 400:
                $message = empty($message) ? "Bad request." : $message;
                break;
            case 404:
                // Break down by entity.
                $entity = $this->getEntity();
                if (empty($entity)) {
                    $message = 'Not found.';
                } else {
                    $code = $this->mapping[$entity]['code'];
                    $message = $this->mapping[$entity]['message'];
                }
                break;
            case 500:
                // If message starts with "Item not found" change to 404.
                $startString = "Item not found";
                if (substr($message, 0, strlen($startString)) === $startString) {
                    $code = '404';
                    // Reformat message if it matches entity path: /v2/entity/id
                    if (preg_match('|.*v2.*\/([0-9+]).*|', $message, $matches))
                    {
                        // Remove v2 prefix.
                        $message = str_replace('v2', '', $message);
                        // Keep text only.
                        $message = preg_replace('/[^A-Za-z ]/', '', $message);
                        // Append entity id.
                        $message = $message . ' id:' . $matches[1];
                    }
                } else {
                    $message = $message . ' Please report this error to support@boxc.com.';
                }

                // TODO: add logging and notification.
                break;
            case 502:
                $message = "Bad Gateway.";
            default:
                //  $code = 'NA;
                // $message = 'Unknown error.';
        }
        return [
            'code' => $code,
            'message' => $message,
        ];
    }

    public function getEntity() {
        $entity = "";

        $request = Request::createFromGlobals();
        $path = $request->getPathInfo();

        if (!empty($path)) {
            $entities = array_keys($this->mapping);
            foreach ($entities as $key) {
                if (strpos($path, $key) !== FALSE) {
                    $entity = $key;
                    break;
                }
            }
        }

        return $entity;
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof FlattenException;
    }
}
