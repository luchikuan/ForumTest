<?php
class DBHelper {

    private static $instance = null;
    private $pdo = null;
    private static $dbAddress = "";
    private static $dbPort = "";
    private static $dbUsername = "";
    private static $dbPassword = "";


    private function __construct()
    {
        global $config;
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC //make the default fetch be an associative array
        ];

        $this->pdo = new PDO("mysql:host=" . self::$dbAddress . ":". self::$dbPort . ";charset=utf8;" ,self::$dbUsername,self::$dbPassword,$options) ;



        //unsafe
        //https://stackoverflow.com/questions/134099/are-pdo-prepared-statements-sufficient-to-prevent-sql-injection
        //$this->queryWithSqlAndDataArray("set names utf8");
    }


    public static function shareHelper($address,$port,$username,$password)
    {
        if (self::$instance == null)
        {

            self::$dbAddress = $address;
            self::$dbPassword = $password;
            self::$dbPort = $port;
            self::$dbUsername = $username;
            self::$instance = new DBHelper();
        }

        return self::$instance;
    }

    public function queryWithSqlAndDataArray($sql,$dataArray = array()){
        if ($this->pdo != null){

            //using prepare statement to connect database
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($dataArray);
            return $stmt->fetchAll();
        }
        return false;
    }

    public function executeSqlWithSqlAndDataArray($sql,$dataArray = array()){
        if ($this->pdo != null){

            //using prepare statement to connect database
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($dataArray);

        }
        return false;
    }

}

ini_set('memory_limit', '-1');
set_time_limit(0);

//backup data file name
$backupName = 'forum_db.bak';

$fillHTMLContent = "";

function getConfigArrayValueWithKey($key,$configData){

    $key = '"' . $key . '"=>"';
    $length = strlen($key);
    $startPos = strpos($configData,$key) + $length;
    $data = '';
    $maxCounter = 500;
    while ($maxCounter){
        $temp = substr($configData,$startPos,1);
        if ($temp == '"'){
            break;
        }

        $data .= $temp;
        $startPos++;
        $maxCounter--;
    }

    if ($maxCounter == 0){
        return "";
    }

    return $data;
}

function readConfigFile(){
    $file = "./core.php";
    if (file_exists($file)) {
        $configData = file_get_contents($file);
        $configData = str_replace(' ', '', $configData);
        $returnData = array(
                "status"=>"正常",
            "data"=>array(
                "db_username"=>getConfigArrayValueWithKey("db_username",$configData),
                "db_name"=>getConfigArrayValueWithKey("db_name",$configData),
                "db_password"=>getConfigArrayValueWithKey("db_password",$configData),
                "db_addr"=>getConfigArrayValueWithKey("db_addr",$configData),
                "db_port"=>getConfigArrayValueWithKey("db_port",$configData),
            )
        );



        if (strlen($returnData["data"]["db_username"])  == 0 || strlen($returnData["data"]["db_name"])  == 0 || strlen($returnData["data"]["db_password"])  == 0 || strlen($returnData["data"]["db_addr"])  == 0 || strlen($returnData["data"]["db_port"])  == 0){
            $returnData["status"] = "找不到配置文件";
        }

        return $returnData;
    }

    return array(
            "status"=>"找不到配置文件",
        "data"=>array()
    );

}

function checkPDOExt(){
    $msg = "正常";
    if (!in_array("pdo_mysql",get_loaded_extensions())){
        $msg = "失敗";
    }
   return $msg;
}

function getBackupData(){

    global $backupName;
    $file = "./" . $backupName;
    if (file_exists($file)) {

       $dataString = file_get_contents($file);
       if (is_string($dataString) && is_array(json_decode($dataString, true))){
           return array(
               "status"=>"正常",
               "data"=>json_decode($dataString, true)
           );
       }

    }

    return array(
            "status"=>"找不到備份文件或格式不正確",
        "data"=>array()
    );

}



if(isset($_GET["action"])){

    //backup
    if ($_GET["action"] == "backup"){
        $fillHTMLContent = '<h1>IAM forum備份數據助手</h1>' .
            '<h2>請檢查是否能正確顯示</h2>' .
            '<h3>PHP-Version：' . phpversion() . '</h3>' .
            '<h3>PHP連接數據庫插件：' . checkPDOExt() . '</h3>' .
            '<h3>連接文件檢測：' . readConfigFile()["status"] . '</h3>' .
            '<a href="?action=backupTest"><button>測試連接數據庫</button></a>' .
            '<button onclick="alert(\'請檢查相關設置\')">不正常</button>' .
            '<a href="./backup.php"><button>返回上一頁</button></a>';

    }elseif ($_GET["action"] == "backupTest"){
        $dbData = readConfigFile();
        try {
            $db = DBHelper::shareHelper($dbData["data"]["db_addr"], $dbData["data"]["db_port"], $dbData["data"]["db_username"], $dbData["data"]["db_password"]);
            $db->queryWithSqlAndDataArray("show databases");
            $fillHTMLContent = "<h1>IAM forum備份數據助手</h1>".
                '<h2 style="color: #00ff00">數據庫連接成功</h2>'.
                '<h3 style="color: #ff8800">接下備份按鍵後，需要一段時間，過程中請不要刷新頁面</h3>'.
                '<a href="?action=cBackup"><button>備份</button></a>'.
                '<a href="?action=backup"><button>上一步</button></a>';
        }catch (Exception $e){
            $fillHTMLContent = '<h1>IAM forum備份數據助手</h1>' .
                '<h2 style="color: #ff0000">請檢查數據庫是否開啟</h2>'.
                '<h2>請檢查是否能正確顯示</h2>' .
                '<h3>PHP-Version：' . phpversion() . '</h3>' .
                '<h3>PHP連接數據庫插件：' . checkPDOExt() . '</h3>' .
                '<h3>連接文件檢測：' . readConfigFile()["status"] . '</h3>' .
                '<a href="?action=backupTest"><button>測試連接數據庫</button></a>' .
                '<button onclick="alert(\'請檢查相關設置\')">不正常</button>' .
                '<a href="?action="><button>返回上一頁</button></a>';
        }

    }elseif ($_GET["action"] == "cBackup"){
        $dbData = readConfigFile();
        try {
            $db = DBHelper::shareHelper($dbData["data"]["db_addr"], $dbData["data"]["db_port"], $dbData["data"]["db_username"], $dbData["data"]["db_password"]);
            $db->queryWithSqlAndDataArray("use forum");
            $userAccountData = $db->queryWithSqlAndDataArray("select * from forum_user_account");
            $articleData = $db->queryWithSqlAndDataArray("select * from forum_article");
            $articleHistoryData = $db->queryWithSqlAndDataArray("select * from forum_article_history");
            $categoryData = $db->queryWithSqlAndDataArray("select * from forum_category");
            $componentData = $db->queryWithSqlAndDataArray("select * from forum_component");
            $commentData = $db->queryWithSqlAndDataArray("select * from forum_comment");

            $outputJsonData = json_encode(array(
                    "article"=>$articleData,
                "articleHistory"=>$articleHistoryData,
                "comment"=>$commentData,
                "component"=>$componentData,
                "category"=>$categoryData,
                "user"=>$userAccountData
            ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);


            file_put_contents("./$backupName",$outputJsonData);

            $fillHTMLContent = '<h1>IAM forum備份數據</h1>'.
                '<h3 style="color: #00ff00">備分完成，感謝您的使用</h3>'.
                '<h3>備份檔案已放在網站根目錄（'. getcwd() .'/' . $backupName .')</h3>'.
                '<h3 style="color: #00aeff">您可以關閉本頁頁</h3>' .
                '<h3>備份統計：</h3>'.
                '<h5>文章：' . count($articleData) . '篇</h5>'.
                '<h5>文章記錄：' . count($articleHistoryData) . '篇</h5>'.
                '<h5>用戶：' . count($userAccountData) . '個</h5>'.
                '<h5>分類：' . count($categoryData) . '個</h5>'.
                '<h5>留言：' . count($commentData) . '則</h5>'.
                '<h5>附件：' . count($componentData) . '件</h5>';
        }catch (Exception $e){
            $fillHTMLContent = '<h1>IAM forum備份數據助手</h1>' .
                '<h2 style="color: #ff0000">備分失敗，備分中請不要操作或關閉數據庫，并稍後重試</h2>'.
                '<h2>請檢查是否能正確顯示</h2>' .
                '<h3>PHP-Version：' . phpversion() . '</h3>' .
                '<h3>PHP連接數據庫插件：' . checkPDOExt() . '</h3>' .
                '<h3>連接文件檢測：' . readConfigFile()["status"] . '</h3>' .
                '<a href="?action=backupTest"><button>測試連接數據庫</button></a>' .
                '<button onclick="alert(\'請檢查相關設置\')">不正常</button>' .
                '<a href="?action="><button>返回上一頁</button></a>';
        }
    }

    //restore
    if ($_GET["action"] == "restore"){
        $fillHTMLContent = '<h1>IAM forum還原數據助手</h1>' .
            '<h2>請檢查是否能正確顯示</h2>' .
            '<h3>PHP-Version：' . phpversion() . '</h3>' .
            '<h3>PHP連接數據庫插件：' . checkPDOExt() . '</h3>' .
            '<h3>連接文件檢測：' . readConfigFile()["status"] . '</h3>' .
            '<h3>數據庫備份文件：' . getBackupData()["status"] . '</h3>'.
            '<a href="?action=restoreTest"><button>測試連接數據庫</button></a>' .
            '<button onclick="alert(\'請檢查相關設置\')">不正常</button>' .
            '<a href="./backup.php"><button>返回上一頁</button></a>';
    }elseif ($_GET["action"] == "restoreTest"){
        $dbData = readConfigFile();
        try {
            $backupData = getBackupData()["data"];
            $db = DBHelper::shareHelper($dbData["data"]["db_addr"], $dbData["data"]["db_port"], $dbData["data"]["db_username"], $dbData["data"]["db_password"]);
            $db->queryWithSqlAndDataArray("show databases");
            $fillHTMLContent = "<h1>IAM forum還原數據助手</h1>".
                '<h2 style="color: #00ff00">數據庫連接成功</h2>'.
                '<h3 style="color: #ff8800">接下還原按鍵後，需要一段時間，過程中請不要刷新頁面</h3>'.
                '<h3 style="color: #ff0000">還原時，在數據庫裡原有的相關數據將會被清空，請緊慎操作!</h3>'.
                '<h3>還原統計：</h3>'.
                '<h5>文章：' . count($backupData["article"]) . '篇</h5>'.
                '<h5>文章記錄：' . count($backupData["articleHistory"]) . '篇</h5>'.
                '<h5>用戶：' . count($backupData["user"]) . '個</h5>'.
                '<h5>分類：' . count($backupData["category"]) . '個</h5>'.
                '<h5>留言：' . count($backupData["comment"]) . '則</h5>'.
                '<h5>附件：' . count($backupData["component"]) . '件</h5>'.
                '<a href="?action=cRestore"><button>還原</button></a>'.
                '<a href="?action=restore"><button>上一步</button></a>';
        }catch (Exception $e){
            $fillHTMLContent = '<h1>IAM forum還原數據助手</h1>' .
                '<h2 style="color: #ff0000">請檢查數據庫是否開啟</h2>'.
                '<h2>請檢查是否能正確顯示</h2>' .
                '<h3>PHP-Version：' . phpversion() . '</h3>' .
                '<h3>PHP連接數據庫插件：' . checkPDOExt() . '</h3>' .
                '<h3>連接文件檢測：' . readConfigFile()["status"] . '</h3>' .
                '<h3>備分文件：' . getBackupData()["status"] . '</h3>'.
                '<a href="?action=backupTest"><button>測試連接數據庫</button></a>' .
                '<button onclick="alert(\'請檢查相關設置\')">不正常</button>' .
                '<a href="./backup.php"><button>返回上一頁</button></a>';
        }
    }elseif ($_GET["action"] == "cRestore"){

        $dbData = readConfigFile();
        try {
            $db = DBHelper::shareHelper($dbData["data"]["db_addr"], $dbData["data"]["db_port"], $dbData["data"]["db_username"], $dbData["data"]["db_password"]);
            $db->executeSqlWithSqlAndDataArray("create database IF NOT EXISTS ".$dbData["data"]["db_name"]);
            $db->executeSqlWithSqlAndDataArray("use ".$dbData["data"]["db_name"]);
            $db->executeSqlWithSqlAndDataArray("set names utf8;");
            //Don't sql execution order
            $db->executeSqlWithSqlAndDataArray("drop table if exists forum_article_history");
            $db->executeSqlWithSqlAndDataArray("drop table if exists forum_comment");
            $db->executeSqlWithSqlAndDataArray("drop table if exists forum_article");
            $db->executeSqlWithSqlAndDataArray("drop table if exists forum_component");
            $db->executeSqlWithSqlAndDataArray("drop table if exists forum_category");
            $db->executeSqlWithSqlAndDataArray("drop table if exists forum_user_account");
            $db->executeSqlWithSqlAndDataArray("create table IF NOT EXISTS forum_user_account( id int primary key auto_increment, nick_name varchar(256) null default \"unknown\", ad_name varchar(256), domain_name varchar(64), icon_image varchar(256) default \"unknown.png\", permission ENUM(\"r\",\"rw\",\"rw+\",\"s\",\"x\"));");
            $db->executeSqlWithSqlAndDataArray("create table IF NOT EXISTS forum_category(id int primary key auto_increment, category_name varchar(1024));");
            $db->executeSqlWithSqlAndDataArray("create table IF NOT EXISTS forum_component( id varchar (256) primary key not null , file_ext varchar (256) not null, owner int not null , nick_name varchar (1024) not null, foreign key (owner) references forum_user_account(id));");
            $db->executeSqlWithSqlAndDataArray("create table IF NOT EXISTS forum_article(id int primary key auto_increment, component_ids text, title varchar(2048) not null , description text null, owner int not null , time_stamp bigint not null, category_id int not null, status enum(\"no-del\",\"n-del\",\"f-del\"), foreign key (category_id) references forum_category(id), foreign key (owner) references forum_user_account(id));");
            $db->executeSqlWithSqlAndDataArray("create table IF NOT EXISTS forum_comment(id int primary key auto_increment, article_id int not null, description text not null, time_stamp bigint not null, owner int not null, foreign key (owner) references forum_user_account(id), foreign key (article_id) references forum_article(id));");
            $db->executeSqlWithSqlAndDataArray("create table IF NOT EXISTS forum_article_history( id int primary key auto_increment, article_id int not null, title varchar(2048) not null, time_stamp bigint not null, description text not null, component_ids text, modifier_id int not null, foreign key (modifier_id) references forum_user_account(id), foreign key (article_id) references forum_article(id));");

            $backupData = getBackupData()["data"];

            $articleData = $backupData["article"];
            $articleHistoryData = $backupData["articleHistory"];
            $commentData = $backupData["comment"];
            $componentData = $backupData["component"];
            $categoryData = $backupData["category"];
            $userAccountData = $backupData["user"];

            //restore user account data
            foreach ($userAccountData as $v){

               $db->executeSqlWithSqlAndDataArray("insert into forum_user_account values(null, :nick_name, :ad_name, :domain_name, :icon_image, :permission)",array(
                   ":nick_name"=>$v["nick_name"],
                   ":ad_name"=>$v["ad_name"],
                   ":domain_name"=>$v["domain_name"],
                   ":icon_image"=>$v["icon_image"],
                   ":permission"=>$v["permission"]
               ));

            }

            //restore category data
            foreach ($categoryData as $v){

                $db->executeSqlWithSqlAndDataArray("insert into forum_category values(null, :category_name)",array(
                    ":category_name"=>$v["category_name"]
                ));

            }

            //restore component data
            foreach ($componentData as $v){
                $db->executeSqlWithSqlAndDataArray("insert into forum_component values(:id, :file_ext, :owner, :nick_name)",array(
                    ":file_ext"=>$v["file_ext"],
                    ":owner"=>$v["owner"],
                    ":nick_name"=>$v["nick_name"],
                    ":id"=>$v["id"]
                ));

            }

            //restore article data
            foreach ($articleData as $v){

                $db->executeSqlWithSqlAndDataArray("insert into forum_article values(null, :component_ids, :title, :description, :owner, :time_stamp, :category_id, :status)",array(
                    ":component_ids"=>$v["component_ids"],
                    ":title"=>$v["title"],
                    ":description"=>$v["description"],
                    ":owner"=>$v["owner"],
                    ":time_stamp"=>$v["time_stamp"],
                    ":category_id"=>$v["category_id"],
                    ":status"=>$v["status"]
                ));

            }

            //restore comment data
            foreach ($commentData as $v){

                $db->executeSqlWithSqlAndDataArray("insert into forum_comment values(null, :article_id, :description, :time_stamp, :owner)",array(
                    ":article_id"=>$v["article_id"],
                    ":description"=>$v["description"],
                    ":time_stamp"=>$v["time_stamp"],
                    ":owner"=>$v["owner"]
                ));

            }

            //restore article history data
            foreach ($articleHistoryData as $v){
                $db->executeSqlWithSqlAndDataArray("insert into forum_article_history values(null, :article_id, :title, :time_stamp, :description, :component_ids, :modifier_id)",array(
                    ":article_id"=>$v["article_id"],
                    ":title"=>$v["title"],
                    ":time_stamp"=>$v["time_stamp"],
                    ":description"=>$v["description"],
                    ":component_ids"=>$v["component_ids"],
                    ":modifier_id"=>$v["modifier_id"]
                ));

            }

            $fillHTMLContent = '<h1>IAM forum還原數據助手</h1>'.
                '<h3 style="color: #00ff00">還原完成，感謝您的使用</h3>'.
                '<h3>您可以關閉本頁頁，并刪除此文件和'. $backupName .'</h3>';

        }catch (Exception $e){
            $fillHTMLContent = '<h1>IAM forum還原數據助手</h1>' .
                '<h2 style="color: #ff0000">還原失敗，還原中請不要操作或關閉數據庫，并稍後重試</h2>'.
                '<h2>請檢查是否能正確顯示</h2>' .
                '<h3>PHP-Version：' . phpversion() . '</h3>' .
                '<h3>PHP連接數據庫插件：' . checkPDOExt() . '</h3>' .
                '<h3>連接文件檢測：' . readConfigFile()["status"] . '</h3>' .
                '<h3>備分文件：' . getBackupData()["status"] . '</h3>'.
                '<a href="?action=restoreTest"><button>測試連接數據庫</button></a>' .
                '<button onclick="alert(\'請檢查相關設置\')">不正常</button>' .
                '<a href="./backup.php"><button>返回上一頁</button></a>';
        }

    }

}

if (strlen($fillHTMLContent) == 0){
    $fillHTMLContent = '<h1>IAM forum 數據庫備份工具</h1><a href="?action=backup"><button>備份數據庫</button></a><a href="?action=restore"><button>還原數據庫</button></a>';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IAM forum 備份工具</title>
</head>
<body>
<div>
    <?php
    echo $fillHTMLContent;
    ?>
</div>

</body>
</html>
