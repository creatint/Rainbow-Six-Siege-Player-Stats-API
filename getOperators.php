<?php
include("config.php");
require_once("Operators.php");

if (empty($_GET)) {
    print "ERROR: Wrong usage";
    die();
}

if (!isset($_GET["appcode"])) {
    print "ERROR: Wrong appcode";
    die();
}

if ($_GET["appcode"] != $config["appcode"]) {
    print "ERROR: Wrong appcode";
    die();
}

if (!isset($_GET["id"]) && !isset($_GET["name"])) {
    print "ERROR: Wrong usage";
    die();
}

include("UbiAPI.php");

$uapi = new UbiAPI($config["ubi-email"], $config["ubi-password"]);

$data = array();
$map = array();

$platform = $config["default-platform"];
if (isset($_GET['platform'])) {
    $platform = $_GET['platform'];
}

$notFound = [];

function printName($uid)
{
    global $uapi, $data, $id, $map, $platform, $notFound;
    $su = $uapi->searchUser("byid", $uid, $platform);
    if ($su["error"] != true) {
        $map[$su['uid']] = array(
            "profile_id" => $su['uid'],
            "nickname" => $su['nick']
        );
        $data[] = array(
            "profile_id" => $su['uid'],
            "nickname" => $su['nick']
        );
    } else {
        $notFound[] = [
            "profile_id" => $uid,
            "error" => [
                "message" => "User not found!"
            ]
        ];
    }
}

function printID($name)
{
    global $uapi, $data, $map, $id, $platform, $notFound;
    $su = $uapi->searchUser("bynick", $name, $platform);
    if ($su["error"] != true) {
        $map[$su['uid']] = array(
            "profile_id" => $su['uid'],
            "nickname" => $su['nick']
        );
        $data[] = array(
            "profile_id" => $su['uid'],
            "nickname" => $su['nick']
        );
    } else {
        $notFound[] = [
            "nickname" => $name,
            "error" => [
                "message" => "User not found!"
            ]
        ];
    }
}

if (isset($_GET["id"])) {
    $str = $_GET["id"];
    if (strpos($str, ',') !== false) {
        $tocheck = explode(',', $str);
    } else {
        $tocheck = array($str);
    }

    foreach ($tocheck as $value) {
        printName($value);
    }
}
if (isset($_GET["name"])) {
    $str = $_GET["name"];
    if (strpos($str, ',') !== false) {
        $tocheck = explode(',', $str);
    } else {
        $tocheck = array($str);
    }

    foreach ($tocheck as $value) {
        printID($value);
    }
}

if (empty($data)) {
    $error = $uapi->getErrorMessage();
    if ($error === false) {
        die(json_encode(array("code" => -1, "data" => $notFound)));
    } else {
        die(json_encode(array("code" => -1, "data" => array(), "error" => $error)));
    }
}

$ids = "";
foreach ($data as $value) {
    $ids = $ids . "," . $value["profile_id"];
}
$ids = substr($ids, 1);

$idresponse = json_decode($uapi->getOperators($ids, $platform), true);

$operatorArray = array();
$operatorOrg = json_decode('{"zofia":{"name":"Zofia","organisation":"GROM"},"castle":{"name":"Castle","organisation":"FBI SWAT"},"jager":{"name":"Jäger","organisation":"GSG 9"},"vigil":{"name":"Vigil","organisation":"SMB"},"sledge":{"name":"Sledge","organisation":"SAS"},"echo":{"name":"Echo","organisation":"SAT"},"fuze":{"name":"Fuze","organisation":"Spetnaz"},"thermite":{"name":"Thermite","organisation":"FBI SWAT"},"blackbeard":{"name":"Blackbeard","organisation":"Navy Seal"},"buck":{"name":"Buck","organisation":"JTF2"},"frost":{"name":"Frost","organisation":"JTF2"},"caveira":{"name":"Caveira","organisation":"Bope"},"ela":{"name":"Ela","organisation":"GROM"},"capitao":{"name":"Capitão","organisation":"BOPE"},"hibana":{"name":"Hibana","organisation":"SAT"},"thatcher":{"name":"Thatcher","organisation":"SAS"},"kapkan":{"name":"Kapkan","organisation":"Spetnaz"},"twitch":{"name":"Twitch","organisation":"GIGN"},"bandit":{"name":"Bandit","organisation":"GSG 9"},"dokkaebi":{"name":"Dokkaebi","organisation":"SMB"},"smoke":{"name":"Smoke","organisation":"SAS"},"iq":{"name":"IQ","organisation":"GSG 9"},"mute":{"name":"Mute","organisation":"SAS"},"alibi":{"name":"Alibi","organisation":"GIS"},"rook":{"name":"Rook","organisation":"GIGN"},"jackal":{"name":"Jackal","organisation":"GEO"},"lion":{"name":"Lion","organisation":"CBRN"},"glaz":{"name":"Glaz","organisation":"Spetnaz"},"finka":{"name":"Finka","organisation":"CBRN"},"valkyrie":{"name":"Valkyrie","organisation":"Navy Seal"},"ying":{"name":"Ying","organisation":"SDU"},"blitz":{"name":"Blitz","organisation":"GSG 9"},"ash":{"name":"Ash","organisation":"FBI SWAT"},"mira":{"name":"Mira","organisation":"GEO"},"pulse":{"name":"Pulse","organisation":"FBI SWAT"},"doc":{"name":"Doc","organisation":"GIGN"},"montagne":{"name":"Montagne","organisation":"GIGN"},"maestro":{"name":"Maestro","organisation":"GIS"},"lesion":{"name":"Lesion","organisation":"SDU"},"maverick":{"name":"Maverick","organisation":"GSUTR"},"clash":{"name":"Clash","organisation":"GSUTR"}}', true);

foreach ($operators as $operator => $info) {
    $operatorArray[$operator] = array();
    $operatorArray[$operator]["images"] = $info["images"];
    $operatorArray[$operator]["category"] = $info["category"];
    $operatorArray[$operator]["index"] = $info["index"];
    $operatorArray[$operator]["id"] = $operator;
    if (isset($operatorOrg[$operator])) {
        $info = $operatorOrg[$operator];
        $operatorArray[$operator]["organisation"] = $info["organisation"];
        $operatorArray[$operator]["name"] = $info["name"];
    }
}

$final = array();
foreach ($idresponse as $id => $value) {
    $final[] = array_merge($value, array("operators" => $operatorArray));
}

if (empty($notFound)) {
    print json_encode(array("code" => 0, "data" => $final));
} else {
    print json_encode(array("code" => -1, "data" => array_merge($final, $notFound)));
}
?>
