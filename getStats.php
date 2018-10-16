<?php
include("config.php");

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
$stats = $config["default-stats"];
$season = -1;

if (isset($_GET['season'])) {
    $season = $_GET['season'];
}

$platform = $config["default-platform"];
if (isset($_GET['platform'])) {
    $platform = $_GET['platform'];
}

if (isset($_GET['stats'])) {
    $stats = $_GET['stats'];
}

$notFound = [];

function printName($uid)
{
    global $uapi, $data, $map, $id, $platform, $notFound;
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
        die(json_encode(array("code"=> -1, "data" => array(), "error" => $error)));
    }
}

$ids = "";
foreach ($data as $value) {
    $ids = $ids . "," . $value["profile_id"];
}
$ids = substr($ids, 1);

$idresponse = json_decode($uapi->getStats($ids, $stats, $platform), true);
$final = array();
foreach ($idresponse["results"] as $value) {
    $id = array_search($value, $idresponse["results"]);
    $final[] = array_merge($value, array("nickname" => $map[$id]["nickname"], "profile_id" => $id, "platform" => $platform));
}
if (empty($notFound)) {
    print str_replace(":infinite", "", json_encode(
        json_encode(array(
            'code' => 0,
            "data" => $final
        ))
    ));
} else {
    print str_replace(":infinite", "", json_encode(json_encode(array(
        'code' => -1,
        "data" => array_merge($final, $notFound)
    ))));
}
?>
