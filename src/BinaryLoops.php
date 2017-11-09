<?php

namespace king052188\BinaryLoops;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

use Carbon\Carbon;

use BLHelper;

class BinaryLoops
{
  private static $config_app;
  private static $config_services;

  public function __construct() {
    $this::$config_app = Config::get('app');
    $this::$config_services = Config::get('services');
  }

  // test function
  public function TestServices($showAll = false) {
    if($showAll) {
      return $this::$config_services;
    }

    if($this->Check_Point()) {
      return array(
        'BinaryLoops' => $this::$config_services["BinaryLoops"]
      );
    }

    return array(
      "Code" => $this::$err_code,
      "Message" => $this::$err_message
    );
  }

  public function Encode($request) {

    $dt = Carbon::now();
    $country = $request["country"] != "" ? $request["country"] : null;
    $muid = BLHelper::generate_unique_id($country);
    $hex_code = sprintf('%06X', mt_rand(0, 0xFFFFFF));
    $encrypted_hexcode = bcrypt($hex_code);
    $passwords = ["Password"=> $hex_code, "Encrypted" => $encrypted_hexcode];

    $member_info = array(
      "member_uid" => $muid,
      "username" => $request["username"] != "" ? $request["username"] : null,
      "password" => $encrypted_hexcode,
      "first_name" => $request["first_name"] != "" ? $request["first_name"] : null,
      "last_name" => $request["last_name"] != "" ? $request["last_name"] : null,
      "country_" => $country,
      "email_" => $request["email"] != "" ? $request["email"] : null,
      "mobile_" => $request["mobile"] != "" ? $request["mobile"] : null,
      "type_" => 2,
      "status_" => -1,
      "connected_to" => $request["connected"] != "" ? $request["connected"] : null,
      "activation_id" => $request["code"] != "" ? $request["code"] : 0,
      'updated_at' => $dt,
      'created_at' => $dt
    );

    return BLHelper::add_member($member_info);
  }

  // classes

  public function getConfigApp($key = null) {
    if($key==null) {
      return $this::$config_app;
    }
    return $this::$config_app[$key];
  }

  public function getConfigServices() {
    return $this::$config_services;
  }

  public function Check_Point() {
    if(!IsSet($this::$config_services["BinaryLoops"])) {
      $this::$err_code = 301;
      $this::$err_message = "Please check your config/services.php";
      return false;
    }

    if(!IsSet($this::$config_services["BinaryLoops"]["host"])) {
      $this::$err_code = 302;
      $this::$err_message = "Please check your [HOST] in config/services.php";
      return false;
    }

    if(!IsSet($this::$config_services["BinaryLoops"]["email"])) {
      $this::$err_code = 303;
      $this::$err_message = "Please check your [EMAIL] in config/services.php";
      return false;
    }

    if(!IsSet($this::$config_services["BinaryLoops"]["license"])) {
      $this::$err_code = 304;
      $this::$err_message = "Please check your [LICENSE] in config/services.php";
      return false;
    }
    return true;
  }

  public function Curl($url = null, $data = []) {

    if($url == null) {
      return ["Status" => 401];
    }

    if(COUNT($data) == 0) {
      return ["Status" => 402];
    }

    // Array to Json
    $toJSON = json_encode($data);

    // Added JSON Header
    $headers= array('Accept: application/json','Content-Type: application/json');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $toJSON);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
  }


}
