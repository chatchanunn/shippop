<?php
/**
 * @author samarkchsngn@gmail.com
 */
namespace Samark\Shippop;

use BadMethodCallException;

class ShippopShipment
{
    /**
     * set order deliver
     * @var array
     */
    protected $orderDelivery;

    /**
     * set parcel
     * @var array
     */
    protected $parcel;

    /**
     * set endpoint api
     * @var string
     */
    protected $urlShippop;

    /**
     * set api key
     * @var string
     */
    protected $apiKey;

    /**
     * set params
     * @var array
     */
    protected $params;

    /**
     * set email
     * @var string
     */
    protected $email;

    /**
     * set confirm id
     * @var string
     */
    protected $purchase_id;

    /**
     * set config list of api endpoint
     * @var array
     */
    protected $configEndpoint = [
        'pricelist' => 'pricelist/',
        'booking' => 'booking/',
        'confirm' => 'confirm/',
        'label' => 'label/',

    ];

    /**
     * config mapping sender name
     * @var array
     */
    protected static $mappingCodeSender = [
        'THP' => 'ไปรษณีย์ไทย EMS',
        'TP2' => 'ไปรษณีย์ ลงทะเบียน',
        'APF' => 'Alphafast',
        'KRY' => 'Kerry Express',
        'RSB' => 'Rush Bike',
        'SKT' => 'Skootar',
        'SCG' => 'SCG Yamato Express',
        'SCGF' => 'SCG Yamato Express Chilled',
        'SCGC' => 'SCG Yamato Express Frozen',
        'NJV' => 'Ninjavan',
        'GRE' => 'Grab Express',
        'GRB' => 'Grab Bike',
        'DHL' => 'DHL',
        'LLM' => 'Lalamove',
        'NKS' => 'Niko Logistic',
    ];

    /**
     * aviableshipment
     * @var array
     */
    protected static $aviableSippment = [
        'THP',
        'TP2',
        'LLM',
    ];

    /**
     * [__construct description]
     */
    public function __construct($url = '', $key = '')
    {
        $this->urlShippop = $url;
        $this->apiKey = $key;
        $this->params['api_key'] = $this->apiKey;
        $this->setEndpoint();
    }

    /**
     * [setEndpoint description]
     * @return void
     */
    protected function setEndpoint()
    {
        foreach ($this->configEndpoint as $key => $url) {
            $this->configEndpoint[$key] = $this->urlShippop . $url;
        }
    }
    /**
     * [genAddress description]
     * @param  array  $addressFrom [description]
     * @param  array  $addressTo   [description]
     * @return void
     */
    protected function genAddress($addressFrom = array(), $addressTo = array())
    {
        $this->orderDelivery = [
            'from' => $addressFrom,
            'to' => $addressTo,
        ];
        return $this->orderDelivery;
    }

    /**
     * [buildParam description]
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function buildParam($params = array())
    {
        $this->genAddress($params['addressFrom'], $params['addressTo']);
        $this->setParcel($params['parcel']);
        $this->params['data'][0] = $this->orderDelivery;
        $this->params['data'][0]['parcel']['from'] = $this->parcel;
        $this->params['data'][0]['parcel']['to'] = $this->parcel;
    }
    /**
     * [setParcel description]
     * @param void
     */
    public function setParcel($parcel)
    {
        $this->parcel['name'] = array_get($parcel, 'name', '-');
        $this->parcel['weight'] = array_get($parcel, 'weight', '-');
        $this->parcel['width'] = array_get($parcel, 'box_width', '-');
        $this->parcel['length'] = array_get($parcel, 'box_large', '-');
        $this->parcel['height'] = array_get($parcel, 'box_height', '-');
        return $this->parcel;
    }

    /**
     * [checkprice description]
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    public function checkprice($params = array())
    {
        $senderList = $this->wrapcCallApi($this->params, 'pricelist');
        return $this->getAviableShipment($senderList);
        // return $this->mappingAviableSender($senderList);
    }

    /**
     * [booking description]
     * @param  string $email [description]
     * @return [type]        [description]
     */
    public function booking($email = '')
    {
        $this->email = $email;
        return $this->wrapcCallApi($this->params, 'booking');
    }

    /**
     * [confirm description]
     * @param  [type] $purchase_id [description]
     * @return [type]              [description]
     */
    public function confirm($purchase_id)
    {
        $this->purchase_id = $purchase_id;
        return $this->wrapcCallApi($this->params, 'confirm');
    }

    /**
     * [label description]
     * @param  [type] $purchase_id [description]
     * @param  string $size        [description]
     * @return [type]              [description]
     */
    public function label($purchase_id, $size = 'a5')
    {
        $this->params['purchase_id'] = $purchase_id;
        $this->params['size'] = $size;
        return $this->wrapcCallApi($this->params, 'label');
    }

    /**
     * [wrapcCallApi description]
     * @param  [type] $post_data [description]
     * @param  [type] $type      [description]
     * @return [type]            [description]
     */
    protected function wrapcCallApi($post_data, $type)
    {
        $params['api_key'] = $this->apiKey;
        if ($type == 'confirm') {
            $params['purchase_id'] = $this->purchase_id;
        } else {
            $params['data'] = $post_data;
            $params['email'] = $this->email;
        }

        $endpoint = $this->configEndpoint[$type];
        return $this->curl($endpoint, $params);
    }
    /**
     * curl shippop
     * @param  [type] $post_data [description]
     * @return [type]            [description]
     */
    protected function curl($endpoint, $params)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_POST, sizeof($params));
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $error = curl_error($curl);
        $result = curl_exec($curl);
        curl_close($curl);

        $result = $this->workAllowed($result);
        $response = json_decode($result, true);
        return $response;
    }

    /**
     * [workAllowed description]
     * @param  [type] $response [description]
     * @return [type]           [description]
     */
    protected function workAllowed($result)
    {
        $new = explode("<br />", $result);
        return $new[0];
    }

    /**
     * [mappingAviableSender description]
     * @param  [type] $senderList [description]
     * @return [type]             [description]
     */
    protected function mappingAviableSender($senderList)
    {
        $senderAviable = array();
        $minArray = array();
        foreach ($senderList['data'] as $key => $senders) {
            foreach ($senders as $keysender => $sender) {
                if (!isset($sender['err_code'])) {
                    $senderAviable[$key][$keysender] = $sender;
                    $senderAviable[$key][$keysender]['name'] =
                    self::$mappingCodeSender[$keysender];
                }
            }
            $minArray[$key] = count($senderAviable[$key]);
        }
        $key = array_keys($minArray, min($minArray));
        return $senderAviable[$key[0]];
    }

    /**
     * [getAviableShipment description]
     * @param  [type] $senderList [description]
     * @return [type]             [description]
     */
    protected function getAviableShipment($senderList)
    {
        $senderAviable = array();
        $minArray = array();
        foreach ($senderList['data'] as $key => $senders) {
            foreach ($senders as $keysender => $sender) {
                if (in_array($keysender, self::$aviableSippment)) {
                    $senderAviable[$key][$keysender] = $sender;
                    $senderAviable[$key][$keysender]['name'] =
                    self::$mappingCodeSender[$keysender];
                }
            }
            $minArray[$key] = count($senderAviable[$key]);
        }
        $key = array_keys($minArray, min($minArray));
        return $senderAviable[$key[0]];

    }
    /**
     * [__call description]
     * @param  [type] $method     [description]
     * @param  [type] $parameters [description]
     * @return [type]             [description]
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (!method_exists($method, $this)) {
            throw new BadMethodCallException("Call to undefined method::{$method}");
        }
    }

    //เช็คราคา
    //echo '<xmp>'.print_r( checkprice() , true).'</xmp>';

    // //Booking + Confirm
    // $booking = booking();
    // $purchase_id = $booking['purchase_id'];
    // $confirm = confirm($purchase_id);
    //echo '<xmp>'.print_r( $confirm , true).'</xmp>';

    //Label / มีหลากหลายขนาด letter, a5, a4
    // $label = label($purchase_id, 'letter');
    // echo $label['html'];
}
