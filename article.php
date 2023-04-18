<?php 
    $articleId = $_GET["aid"];

    $db_name = "test";
    $db_username ="test";
    $db_password = "test";
    $db_addr = "test";
    $db_port = "1234";

    $dsn = "mysql:dbname=$db_name;host=$db_addr:$db_port;charset=utf8";
    $db = new PDO($dsn, $db_username, $db_password);

    $result = $db->query("select * from forum_article left join forum_user_account on forum_article.owner = forum_user_account.id where forum_article.id = $articleId");

    if($result){
        foreach($result as $row){
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Sharing - <?php echo $row['title']?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/font.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <style>
        body{
            padding: 30px 30px;
            font-family: "Microsoft JhengHei";
            font-size: 16px;
        }
        table tr td{
            padding-bottom: 10px;
            vertical-align: top;
        }
        .bold{
            font-weight: bold;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="bold" width="60">標題:</td>
            <td class="bold"><?php echo $row['title']?></td>
        </tr>
        <tr>
            <td class="bold">作者:</td>
            <td class="bold"><?php echo $row['ad_name']?></td>
        </tr>
        <tr>
            <td class="bold" style="padding-bottom:30px">日期:</td>
            <td class="bold"><?php echo date("Y-m-d",$row['time_stamp'])?></td>
        </tr>
        <tr>
            <td class="bold">內容:</td>
            <td><?php echo $row['description']?></td>
        </tr>
        <?php
            if($row['component_ids']){
        ?>
        <tr>
            <td class="bold">附件:</td>
            <td>
            <?php 
                $components = explode(',',$row['component_ids']);
                foreach($components as $component){
                    $dbcomps = $db->query("select * from forum_component where id = '$component'");
                    if($dbcomps){
                        foreach($dbcomps as $dbcomp){
                            $compID = $dbcomp['id'];
                            $compExt = $dbcomp['file_ext'];
                            $compName = $dbcomp['nick_name'];
                            echo "<a href='./REAL_DATA/$compID.$compExt' download='$compName.$compExt'>$compName.$compExt</a><br>";
                        }
                    }
                    else{
                        echo "Not data found!";
                    }
                }
            ?>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
    <?php }}?>
    <br><a href="index.html"><返回GSTF知識分享首頁</a>
</body>
</html>