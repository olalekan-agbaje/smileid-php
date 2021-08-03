<?php
spl_autoload_register(function($class) {
    require_once($class.'.php');
});
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

require 'vendor/autoload.php';

class IdApi
{
    const SID_SERVERS = [
        'https://3eydmgh10d.execute-api.us-west-2.amazonaws.com/test',
        'https://la7am6gdm8.execute-api.us-west-2.amazonaws.com/prod'
    ];
    public Signature $sig_class;
    private String $partner_id;
    private String $default_callback;
    private String $sid_server;

    /**
     * IdApi constructor.
     * @param $partner_id
     * @param $default_callback
     * @param $api_key,
     * @param $sid_server
     * @throws Exception
     */
    public function __construct($partner_id, $default_callback, $api_key, $sid_server)
    {
        $this->partner_id = $partner_id;
        $this->default_callback = $default_callback;
        $this->sig_class = new Signature($api_key, $partner_id);
        if(strlen($sid_server) == 1) {
            if(intval($sid_server) < 2) {
                $this->sid_server = self::SID_SERVERS[intval($sid_server)];
            } else {
                throw new Exception("Invalid server selected");
            }
        } else {
            $this->sid_server = $sid_server;
        }
    }

    /**
     * @param $partner_params
     * @param $id_info
     * @param $use_async
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function submit_job($partner_params, $id_info, $use_async): ResponseInterface
    {
        $b = $this->sig_class->generate_sec_key();
        $sec_key = $b[0];
        $timestamp = $b[1];

        $data = array(
            'language' => 'php',
            'callback_url' => $this->default_callback,
            'partner_params' => $partner_params,
            'sec_key' => $sec_key,
            'timestamp' => $timestamp,
            'partner_id' => $this->partner_id
        );
        $data = array_merge($data, $id_info);
        $json_data = json_encode($data, JSON_PRETTY_PRINT);

        $client = new Client([
            'base_uri' => $this->sid_server,
            'timeout'  => 5.0
        ]);
        $url = $use_async ? '/async_id_verification' : '/id_verification';
        return $client->post($url, [
            'content-type' => 'application/json',
            'body' => $json_data
        ]);
    }
}
