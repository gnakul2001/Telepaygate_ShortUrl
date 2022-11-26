<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Request;
use App\Models\ShortUrls as ST;
use Exception;
/**
 * ShortLinks
 */
class ShortLinks {
    protected static $chars = "abcdefghijklmnopqrstuvwxyz|ABCDEFGHIJKLMNOPQRSTUVWXYZ|0123456789";

    public function __construct(){
		date_default_timezone_set("ASIA/KOLKATA");
    }

    public function redirectUrl($code){
    	try{
		    return $this->shortCodeToUrl($code);
		}catch(Exception $e){
			return abort(404);
		}
    }

    public function redirectUrlByType($type, $code){
    	try{
		    return $this->shortCodeToUrl($code, $type);
		}catch(Exception $e){
			return abort(404);
		}
    }

    protected function shortCodeToUrl($code, $type=null){
        if(empty($code)) {
            throw new Exception("No short url was supplied.");
        }
        $utmsource = null;
        $code = explode("_", $code);
        if (count($code)>1) {
            $utmsource = $code[0];
            array_shift($code);
        }
        $code = implode("_",$code);
        if($this->validateShortCode($code) == false){
            throw new Exception("Short url does not have a valid format.");
        }
        $urlRow = $this->getUrlFromDB($code, $type);
        if(!isset($urlRow["short_code"])){
            throw new Exception("Short url does not appear to exist.");
        }

        if (boolval($urlRow["isTemp"])) {
            if (boolval($urlRow["isUsed"])) {
                throw new Exception("Short url is Temporary and should be used for once.");
            }
            $urlRow->update(["isUsed" => 1]);
        }

        $this->incrementCounter($urlRow["short_code"], $type);
        if (!empty($utmsource)) {
            $utm_source = $this->utmsource($utmsource);
            if (empty($utm_source)) {
                $url = $urlRow["long_url"];
            }else{
                $url = $urlRow["long_url"] . (parse_url($urlRow["long_url"], PHP_URL_QUERY) ? '&' : '?') . 'utm_source=' . $utm_source;
            }
        }else{
            $url = $urlRow["long_url"];
        }

        return redirect($url);
    }

    protected function getIpLocation(){
        $ip = $_SERVER['REMOTE_ADDR'];
        $url="http://ipinfo.io/" . $ip . "/json?token=b90204b746daad";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
        $response1 = curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return (!empty($response) && $response != 404)? $response1:"";
    }

    protected function utmsource($utmsource){
        switch (strtolower($utmsource)) {
            case 'a':
                return "facebook";
                break;
            case 'b':
                return "whatsapp";
                break;
            case 'c':
                return "twitter";
                break;
            case 'd':
                return "instagram";
                break;
            case 'e':
                return "linkedin";
                break;
            default:
                return null;
                break;
        }
    }

    protected function validateShortCode($code){
        $rawChars = str_replace('|', '', self::$chars);
        return preg_match("|[".$rawChars."]+|", $code);
    }

    protected function getUrlFromDB($code, $type=null){
    	$st = ST::where("short_code", $code)->where("type", $type)->first();
    	return $st;
    }

    protected function incrementCounter($short_code, $type=null){
        $data = $this->getUrlFromDB($short_code, $type);
        if (isset($data["ip_data"])) {
            $ip_data = $data["ip_data"];
            $ip_data = json_decode($ip_data,true);
            $ip_data_user = $this->getIpLocation();
            $ip_data[] = json_decode($ip_data_user,true);
            $ip_data = json_encode($ip_data);
            ST::where("short_code",$short_code)->increment("counter", 1, ["ip_data"=>$ip_data]);
        }
    }
}
