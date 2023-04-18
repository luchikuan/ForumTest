<?php
/*
 *  Remark:
 *  This is the whole function part in the forum.
 *  There are four parts in this php file.
 *  First part is the config.
 *  Second part is handle the input from the user
 *  Third part the core function
 *  Four part is the core framework in this forum.
 *
 *  All the data will be formatted to JSON type.
 *
 *  Creator: LEONG KUOK FU
 *  Copyright © 2020 IAM. All rights reserved.
 * */


set_time_limit(0);


/*--------------------------------------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------- First Part ------------------------------------------------------------*/
/*--------------------------------------------------------------------------------------------------------------------------------------*/


$config = array(
    "db_name"=>"test",
    "db_username"=>"test",
    "db_password" => "test",
    "db_addr"=>"test",
    "db_port"=>"1234",
    "description_capture_string" => 450,
    "upload_cache_file_path"=> './TEMP_DATA/',
    "upload_file_path"=> "./REAL_DATA/",
    "download_file_GET_variable_name" => "df"

);

/*--------------------------------------------------------------------------------------------------------------------------------------*/
/*------------------------------------------------------------- Second Part ------------------------------------------------------------*/
/*--------------------------------------------------------------------------------------------------------------------------------------*/

session_name("L_K_F");
session_start();

$input = new InputManager();
$action = $input->getActionName();
$dispatch = new Dispatcher();
$dispatch->dispatchToFunction($action,$input->getRequestData());


//
//echo "<pre>";
//var_dump($_SESSION);

//FileHelper::createAnUpload("txt","tom",1);
//echo FileHelper::uploadFile("638477cfc1ef382e742500b5806722f3","YXNkc2E=",0);
//echo FileHelper::removeUploadedFile("638477cfc1ef382e742500b5806722f3","txt");



/*--------------------------------------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------- Third Part ------------------------------------------------------------*/
/*--------------------------------------------------------------------------------------------------------------------------------------*/

//main screen
function selfStatistic(){

    $returnData = array();

    $userId = $_SESSION["user_id"];

    $selfArticleModel = Article::getAllArticleModelsByUserId($userId);
    $totalArticleModel = Article::getAllArticle();
    $uComment = Comment::getCommentModelsByUserId($userId);
    $userModel = UserAccount::getUserAccountModelById($userId);

    $returnData["totalAmount"] = count($totalArticleModel);
    $returnData["selfAmount"] = count($selfArticleModel);
    $returnData["selfCommentAmount"] = count($uComment);
    $returnData["selfName"] = $userModel[0]->getAdName();

    OutputManager::outputMsg(200,$returnData);

}
function getRecentArticle(){

    $returnData = array();
    global $config;
    $models = Article::getRecentAddArticleModels();
    foreach ($models as $value){
        $articleTemp = array();

        $articleTemp["articleId"] =  intval($value->getId());
        $articleTemp["commentCount"] = count(Comment::getCommentModelsByArticleId($value->getId()));
        $articleTemp["articleTitle"] = $value->getTitle();
        $temp =strip_tags($value->getDescription());
        $articleTemp["articleDescription"] = substr(str_replace("\n","",$temp),0,intval($config["description_capture_string"]));
        $articleTemp["articleDate"] = date("Y年m月d日",intval($value->getTimeStamp()));
        $articleTemp["articleOwnerId"] = intval($value->getOwner());
        $userModel = UserAccount::getUserAccountModelById($value->getOwner())[0];
        $articleTemp["articleOwnerName"] = $userModel->getNickName();
        $articleTemp["articleOwnerImageName"] =  $userModel->getIconImage();
        $articleTemp["categoryId"] = intval($value->getCategoryId());
        $articleTemp["categoryTitle"] = Category::getCategoryModelById($value->getCategoryId())[0]->getCategoryName();

        array_push($returnData,$articleTemp);

    }


    if (count($returnData) == 0){
        OutputManager::outputMsg(201,"No Record");
    }else{
        OutputManager::outputMsg(200,$returnData);
    }

}

//get other detail data use
function search($data){

    Verify::verifyInputDataInString($data);

    $keyword = $data["p1"];

    $returnData = array();
    $returnData["keyword"] = $keyword;

    global $config;

    $articleModels = Article::findArticleModelsByKeyword($keyword);
    $categoryModels = Category::findCategoryModelsByKeyword($keyword);
    $commentModels = Comment::findCommentModelsByKeyword($keyword);

    $temp1 = array(); //article
    $temp2 = array(); //category
    $temp3 = array(); //comment

    foreach ($articleModels as $value){

        $temp = array();
        $temp["articleTitle"] = $value->getTitle();
        $temp["articleDescription"] = substr(strip_tags($value->getDescription()),0,$config["description_capture_string"]);
        $temp["articleId"] = intval($value->getId());
        $temp["articleDate"] = date("Y年m月d日",intval($value->getTimeStamp()));
        $model = Category::getCategoryModelById($value->getCategoryId())[0];
        $temp["categoryId"] = intval($model->getId());
        $temp["categoryTitle"] = $model->getCategoryName();

        array_push($temp1,$temp);
    }

    foreach ($categoryModels as $value){

        $temp = array();
        $temp["categoryId"] = $value->getId();
        $temp["categoryTitle"] = $value->getCategoryName();

        array_push($temp2,$temp);

    }

    foreach ($commentModels as $value){

        $temp = array();
        $temp["commentId"] = intval($value->getId());
        $temp["commentDescription"] = $value->getDescription();
        $temp["commentDate"] = date("Y年m月d日",intval($value->getTimeStamp()));
        $articleModel = Article::getArticleModelById($value->getArticleId())[0];
        $categoryModel = Category::getCategoryModelById($articleModel->getCategoryId())[0];
        $temp["articleId"] = intval($articleModel->getId());
        $temp["articleTitle"] = $articleModel->getTitle();
        $temp["categoryId"] = intval($categoryModel->getId());
        $temp["categoryTitle"] = $categoryModel->getCategoryName();
        $tUsermodel = UserAccount::getUserAccountModelById($value->getOwner());
        if (isset($tUsermodel[0])){
            $tUsermodel = $tUsermodel[0];
            $temp["commentOwner"] = $tUsermodel->getNickName();
        }

        array_push($temp3,$temp);

    }

    if (count($temp1) == 0 && count($temp2) == 0 && count($temp3) == 0){
        OutputManager::outputMsg(201,"No Record");
    }

    $returnData["articles"] = $temp1;
    $returnData["categories"] = $temp2;
    $returnData["comments"] = $temp3;

    OutputManager::outputMsg(200,$returnData);

}

//user account
function getAllUserPermission(){
    $userModels = UserAccount::getAllUserAccountModels();

    $returnData =  array();
    $xPermission = array();
    $rPermission = array();
    $rwPermission = array();
    $rwPlusPermission = array();
    $sPermission = array();

    foreach ($userModels as $value){
        $temp = array();
        $temp["userId"] = intval($value->getId());
        $temp["nickName"] = $value->getNickName();
        $temp["adName"] = $value->getAdName();
        $temp["domainName"] = $value->getDomainName();

        if ($value->getPermission() == "s"){
            array_push($sPermission,$temp);
        }
        if ($value->getPermission() == "r"){
            array_push($rPermission,$temp);
        }

        if ($value->getPermission() == "rw"){
            array_push($rwPermission,$temp);
        }

        if ($value->getPermission() == "rw+"){
            array_push($rwPlusPermission,$temp);
        }

        if ($value->getPermission() == "x"){
            array_push($xPermission,$temp);
        }
    }

    if (count($rPermission) == 0 && count($xPermission) == 0 && count($rwPermission) == 0 && count($rwPlusPermission) == 0 && count($sPermission) == 0){
        OutputManager::outputMsg(201,"No Record");
    }

    $returnData["r"] = $rPermission;
    $returnData["rw"] = $rwPermission;
    $returnData["rw+"] = $rwPlusPermission;
    $returnData["s"] = $sPermission;
    $returnData["x"] = $xPermission;
    OutputManager::outputMsg(200,$returnData);

}
function modifyUserPermission($data){

    Verify::verifyInputDataInString($data,2);

    $userId = intval($data["p1"]);
    $permission = $data["p2"];

    $tempResult = UserAccount::getUserAccountModelById($userId);
    if (count($tempResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }
    //"r","rw","rw+","s","x"
    if (!($permission == "r" || $permission == "rw" || $permission == "rw+" || $permission == "s" || $permission == "x")){
        OutputManager::outputMsg(404,"Data Error");
    }

    $userModel = $tempResult[0];
    $userModel->setPermission($permission);
    $result = $userModel->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function changeSelfNickName($data){

    Verify::verifyInputDataInString($data);

    $nickName = $data["p1"];
    $userId = $_SESSION["user_id"];

    $tempUserResult = UserAccount::getUserAccountModelById($userId);
    if (count($tempUserResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }
    $model = $tempUserResult[0];
    $model->setNickName($nickName);
    $result = $model->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function changeSelfIconImage($data){

    Verify::verifyInputDataInString($data);

    $iconImage = $data["p1"];
    $userId = $_SESSION["user_id"];

    $tempUserResult = UserAccount::getUserAccountModelById($userId);
    if (count($tempUserResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }
    $model = $tempUserResult[0];
    $model->setIconImage($iconImage);
    $result = $model->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function getSelfIcon(){

    $userId = $_SESSION["user_id"];

    $tempUserResult = UserAccount::getUserAccountModelById($userId);
    if (count($tempUserResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }

    $returnData = array(
      "imageFileId"=>$tempUserResult[0]->getIconImage()
    );

    OutputManager::outputMsg(200,$returnData);

}
function searchUsername($data){

    Verify::verifyInputDataInString($data);

    $keyword = $data["p1"];

    $models = UserAccount::getUserAccountModelByKeyword($keyword);

    $returnData = array();
    foreach ($models as $model){
        $temp = array();
        $temp["userId"] = $model->getId();
        $temp["adName"] = $model->getAdName();
        $temp["nickName"] = $model->getNickName();
        array_push($returnData,$temp);

    }

    if (count($returnData) == 0){
        OutputManager::outputMsg(201,"no record");
    }

    OutputManager::outputMsg(200,$returnData);

}

/*permission*/
function getSelfPermission(){

    $id =  $_SESSION["user_id"];
    $userModel = UserAccount::getUserAccountModelById($id);

    $returnData = array();


    if (count($userModel) == 0){
        $returnData["permission"] = "x";
        OutputManager::outputMsg(201,$returnData);
    }
    $userModel = $userModel[0];
    $returnData["permission"] = $userModel->getPermission();
    OutputManager::outputMsg(200,$returnData);
}


//Article Part

function getAllSelfArticles(){

    $id = $_SESSION["user_id"];
    global $config;
    $userModel = UserAccount::getUserAccountModelById($id)[0];
    $articleModels = Article::getAllArticleModelsByUserId($id);

    $returnData = array();
    $returnData["ownerId"] = intval($userModel->getId());
    $returnData["ownerNickName"] = $userModel->getNickName();
    $returnData["ownerImage"] = $userModel->getIconImage();
    $temp2 = array();

    foreach ($articleModels as $value){
        $temp = array();
        $categoryModel = Category::getCategoryModelById($value->getCategoryId())[0];
        $temp["categoryId"] = intval($value->getCategoryId());
        $temp["categoryTitle"] = $categoryModel->getCategoryName();
        $temp["articleTitle"] = $value->getTitle();
        $temp["articleDescription"] = substr(strip_tags($value->getDescription()),0,$config["description_capture_string"]);
        $temp["articleDate"] = date("Y年m月d日",intval($value->getTimeStamp()));

        array_push($temp2,$temp);
    }

    $returnData["articles"] = $temp2;

    if (count($temp2) == 0){
        OutputManager::outputMsg(201,"no record");
    }

    OutputManager::outputMsg(200,$temp2);

}
function getArticlesByCategory($id){

    Verify::verifyInputDataInId($id);

    $id = $id["p1"];

    //prevent sql injection
    $id = intval($id);
    global $config;

    $returnData = array();
    $categoryResult = Category::getCategoryModelById($id);
    if (count($categoryResult) == 0){
        OutputManager::outputMsg(201,"No Record");
    }
    $categoryModel = $categoryResult[0];
    $returnData["categoryId"] = $id;
    $returnData["categoryTitle"] = $categoryModel->getCategoryName();
    $tempResult = Article::getAllArticleModelsBaseOnCategoryId($id);

    $temp = array();

    foreach ($tempResult as $value){

        $article = array();
        $article["articleTitle"] = $value->getTitle();
        $a =strip_tags($value->getDescription());
        $article["articleDescription"] = substr(str_replace("\n","",$a),0,intval($config["description_capture_string"]));
        $article["articleId"] = intval($value->getId());
        $article["articleDate"] = date("Y年m月d日", intval($value->getTimeStamp()));
        $userModel = UserAccount::getUserAccountModelById($value->getOwner())[0];
        $article["ownerId"] = intval($userModel->getId());
        $article["ownerName"] = $userModel->getNickName();
        $article["ownerImage"] = $userModel->getIconImage();
        $article["commentCount"] = count(Comment::getCommentModelsByArticleId($value->getId()));

        array_push($temp,$article);

    }

    if (count($temp) == 0){
        OutputManager::outputMsg(201,"No Record");
    }

    $returnData["articles"] = $temp;

    OutputManager::outputMsg(200,$returnData);

}
function getArticle($id){

    Verify::verifyInputDataInId($id);

    $id = $id["p1"];

    //prevent sql injection
    $id = intval($id);

    $returnData = array();

    $articleModes = Article::getArticleModelById($id);

    if (!isset($articleModes[0])){
        OutputManager::outputMsg(404,"Data Error");
    }

    $articleMode = $articleModes[0];

    if ($articleMode->getStatus() != "no-del"){
        OutputManager::outputMsg(404,"Data Error");
    }

    $returnData["articleId"] = intval($articleMode->getId());
    $returnData["ownerId"] = intval($articleMode->getOwner());
    $returnData["articleTitle"] = $articleMode->getTitle();
    $tUserModel = UserAccount::getUserAccountModelById($articleMode->getOwner())[0];
    $returnData["ownerName"] = $tUserModel->getNickName();
    $tCategoryModel = Category::getCategoryModelById($articleMode->getCategoryId())[0];
    $returnData["categoryName"] = $tCategoryModel->getCategoryName();
    $returnData["description"] = $articleMode->getDescription();
    $returnData["lastModifyTime"] = date("Y年m月d日",intval($articleMode->getTimeStamp()));
    $componentIdsArray = explode(",",$articleMode->getComponentIds());
    $returnData["componentIds"] = array();
    $returnData["componentsName"] = array();
    if (strlen($articleMode->getComponentIds()) == 0){
        $returnData["componentIds"] = array();
        $returnData["componentsName"] = array();
    }else{

        $tempIds = array();
        $tempNames = array();
        foreach ($componentIdsArray as $value){

            $tModel = Component::getComponentModelById($value);
            if (isset($tModel[0])){
                array_push($tempIds,$value);
                array_push($tempNames,$tModel[0]->getNickName() . "." . $tModel[0]->getFileExt());
            }
        }
        $returnData["componentIds"] = $tempIds;
        $returnData["componentsName"] = $tempNames;
    }
    $commentData = array();
    $commentModels =  Comment::getCommentModelsByArticleId($articleMode->getId());
    foreach ($commentModels as $value){
        $temp = array();
        $temp["commentOwnerId"] = intval($value->getOwner());
        $userModel = UserAccount::getUserAccountModelById($value->getOwner())[0];
        $temp["commentOwnerName"] = $userModel->getNickName();
        $temp["commentOwnerImage"] = $userModel->getIconImage();
        $temp["commentDate"] = date("Y年m月d日",intval($value->getTimeStamp()));
        $temp["commentDescription"] = $value->getDescription();
        array_push($commentData,$temp);
    }

    $returnData["comments"] = $commentData;

    OutputManager::outputMsg(200,$returnData);

}
function getRubbishBinArticles(){

    $returnData = array();
    global $config;
    $articleModels = Article::getAllArticleModelsFromRecycleBin();
    foreach ($articleModels as $value){
        $temp =  array();

        $temp["articleId"] = intval($value->getId());
        $temp["articleTitle"] = $value->getTitle();
        $a =strip_tags($value->getDescription());
        $temp["articleDescription"] =  substr(str_replace("\n","",$a),0,intval($config["description_capture_string"]));
        $temp["articleDate"] = date("Y年m月d日", intval($value->getTimeStamp()));
        $temp["commentCount"] = count(Comment::getCommentModelsByArticleId($value->getId()));
        $temp["categoryName"] = Category::getCategoryModelById($value->getCategoryId())[0]->getCategoryName();
        $userModel = UserAccount::getUserAccountModelById($value->getOwner())[0];
        $temp["ownerId"] = intval($userModel->getId());
        $temp["ownerName"] = $userModel->getNickName();
        $temp["ownerImage"] = $userModel->getIconImage();

        array_push($returnData,$temp);

    }

    if (count($returnData) == 0){
        OutputManager::outputMsg(201,"No Record");
    }

    OutputManager::outputMsg(200,$returnData);



}
function addArticle($data){

    for($i = 1;$i <= 4;$i++){
        $key = "p".$i;
        if (!isset($data[$key])){
            OutputManager::outputMsg(405,"data error");
        }
    }

    $owner = $_SESSION["user_id"];
    $title = $data["p1"];
    $description = $data["p2"];
    $componentIdArray = $data["p3"];
    $categoryId = intval($data["p4"]);

    if (strlen($description) == 0){
        OutputManager::outputMsg(404,"data error");
    }

    if (strlen($title) == 0){
        OutputManager::outputMsg(404,"data error");
    }

    if ($categoryId < 1){
        OutputManager::outputMsg(404,"data error");
    }

    $categoryRecords = Category::getCategoryModelById($categoryId);
    if (count($categoryRecords) == 0){
        OutputManager::outputMsg(404,"data error");
    }

    foreach ($componentIdArray as $componentId){
        $temp = Component::getComponentModelById($componentId);
        if (count($temp) == 0){
          OutputManager::outputMsg(404,"data error");
        }

    }

    $model = new Article();
    $model->setOwner($owner);
    $model->setTitle($title);
    $model->setTimeStamp(time());
    $model->setCategoryId($categoryId);
    $model->setComponentIds(implode(",",$componentIdArray));
    $model->setDescription($description);
    $model->setStatus("no-del");
    $result = $model->save2DB();

    if ($result){
        OutputManager::outputMsg(200,"ok");
    }else{
        OutputManager::outputMsg(500,"error");
    }

}
function modifyArticle($data){

    for($i = 1;$i <= 5;$i++) {
        $key = "p" . $i;
        if (!isset($data[$key])) {
            OutputManager::outputMsg(405, "data error");
        }
    }

    $articleId = intval($data["p1"]);
    $description = $data["p2"];
    $title = $data["p3"];
    $componentIdArray = explode(",",$data["p4"]);
    $categoryId = intval($data["p5"]);
    $owner = $_SESSION["user_id"];

    if ($articleId < 1){
        OutputManager::outputMsg(404,"data error");
    }

    if (strlen($description) == 0){
        OutputManager::outputMsg(404,"data error");
    }

    if (strlen($title) == 0){
        OutputManager::outputMsg(404,"data error");
    }


    if (strlen($data["p4"]) != 0){
        foreach ($componentIdArray as $componentId){

            $temp = Component::getComponentModelById($componentId);
            if (count($temp) == 0){
                echo "a";
                OutputManager::outputMsg(404,"data error");
            }
        }
    }

    $testCategory = Category::getCategoryModelById($categoryId);
    if (count($testCategory) == 0){
        OutputManager::outputMsg(404,"data error");
    }

    $temp = Article::getArticleModelById($articleId);
    if (count($temp)== 0){
        OutputManager::outputMsg(404,"data error");
    }

    $articleModel = $temp[0];

    $historyModel = new ArticleHistory();
    $historyModel->setTitle($articleModel->getTitle());
    $historyModel->setDescription($articleModel->getDescription());
    $historyModel->setTimeStamp($articleModel->getTimeStamp());
    $historyModel->setArticleId($articleId);
    $historyModel->setModifierId($articleModel->getOwner());
    $historyModel->setComponentIds($articleModel->getComponentIds());
    $result = $historyModel->save2DB();

    if (!$result){
        OutputManager::outputMsg(500,"error");
    }

    $articleModel->setTitle($title);
    $articleModel->setDescription($description);
    $articleModel->setComponentIds(implode(",",$componentIdArray));
    $articleModel->setOwner($owner);
    $articleModel->setCategoryId($categoryId);
    $articleModel->setTimeStamp(time());
    $result = $articleModel->save2DB();

    if (!$result){
        OutputManager::outputMsg(500,"error");
    }

    OutputManager::outputMsg(200,"ok");

}
function completelyRemoveArticle($data){
    Verify::verifyInputDataInId($data);

    $articleId = $data["p1"];

    $tempResult = Article::getArticleModelById($articleId);
    if (count($tempResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }

    $model = $tempResult[0];
    $model->setStatus("f-del");
    $result = $model->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }
}
function removeArticleToRubbishBin($data){

   Verify::verifyInputDataInId($data);

   $articleId = $data["p1"];

   $tempResult = Article::getArticleModelById($articleId);
   if (count($tempResult) == 0){
       OutputManager::outputMsg(404,"Data Error");
   }

   $model = $tempResult[0];
   $model->setStatus("n-del");
   $result = $model->save2DB();
    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function recoverFromRubbishBin($data){
    Verify::verifyInputDataInId($data);

    $articleId = $data["p1"];

    $tempResult = Article::getArticleModelById($articleId);
    if (count($tempResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }

    $model = $tempResult[0];
    $model->setStatus("no-del");
    $result = $model->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }
}

//Category Part
function getAllCategory(){

    $returnData = array();

    $categoryModels = Category::getAllCategoryModels();
    foreach ($categoryModels as $value){
        $temp = array();
        $temp["categoryId"] = intval($value->getId());
        $temp["categoryName"] = $value->getCategoryName();

        array_push($returnData,$temp);
    }

    if (count($returnData) == 0){
        OutputManager::outputMsg(201,"No Record");
    }else{
        OutputManager::outputMsg(200,$returnData);
    }

}
function addCategory($data){

    Verify::verifyInputDataInString($data);

    $title = $data["p1"];

    $categoryModel = new Category();
    $categoryModel->setCategoryName($title);
    $result = $categoryModel->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function modifyCategory($data){

    Verify::verifyInputDataInString($data,2);
    $categoryId = intval($data["p1"]);
    $categoryTitle = $data["p2"];

    $CategoryModelResult = Category::getCategoryModelById($categoryId);

    if (count($CategoryModelResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }

    $categoryModel = $CategoryModelResult[0];
    $categoryModel->setCategoryName($categoryTitle);
    $result = $categoryModel->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function removeCategory($data){

    Verify::verifyInputDataInId($data);

    $categoryId = $data["p1"];

    if (count(Category::getCategoryModelById($categoryId)) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }

    $articleModels = Article::getAllArticleModelsBaseOnCategoryId($categoryId,false);

    //remove relationship record
    foreach ($articleModels as $articleModel){

        //remove comment
        $commentModels = Comment::getCommentModelsByArticleId($articleModel->getId());
        foreach ($commentModels as $commentModel) {
            Comment::removeCommentById($commentModel->getId());
        }

        //remove article history
        $articleHistoryModels = ArticleHistory::getArticleHistoryModelsByArticleId($articleModel->getId());
        foreach ($articleHistoryModels as $articleHistoryModel){
            ArticleHistory::removeArticleHistoryModelsById($articleHistoryModel->getId());
        }

        //remove component
        if ($articleModel->getComponentIds() != "none"){
            $componentIds = explode(",",$articleModel->getComponentIds());
            //call internal method for removing component
            foreach ($componentIds as $componentId){

                $temp = Component::getComponentModelById($componentId);

                if (count($temp) != 0){
                    $model = $temp[0];
                   $result = FileHelper::removeUploadedFile($model->getId(),$model->getFileExt());
                   if ($result){
                       Component::removeComponentModelById($componentId);
                   }
                }

            }
        }

        //remove article
        Article::removeArticleRecordByArticleId($articleModel->getId());


    }

    $result = Category::removeCategoryModelById($categoryId);

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}

//Comment Part
function addComment($data){

    Verify::verifyInputDataInString($data,2);

    $ownerId = $_SESSION["user_id"];
    $commentDescription = $data["p1"];
    $articleId = intval($data["p2"]);

    //checking
    if (count(Article::getArticleModelById($articleId)) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }
    if (count(UserAccount::getUserAccountModelById($ownerId)) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }

    $commentModel = new Comment();
    $commentModel->setDescription($commentDescription);
    $commentModel->setArticleId($articleId);
    $commentModel->setOwner($ownerId);
    $commentModel->setTimeStamp(time());
    $result = $commentModel->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function modifyComment($data){

    Verify::verifyInputDataInString($data,2);

    $id = intval($data["p1"]);
    $description = $data["p2"];
    $ownerId = $_SESSION["user_id"];

    $tempResult = Comment::getCommentById($id);
    if (count($tempResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }

    $commentModel = $tempResult[0];

    //only available modify his/her self comment
    if ($commentModel->getOwner() != $ownerId){
        OutputManager::outputMsg(403,"Denied");
    }

    $commentModel->setDescription($description);
    $result = $commentModel->save2DB();

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function removeComment($data){

    Verify::verifyInputDataInId($data);

    $id = intval($data["p1"]);
    $ownerId = $_SESSION["user_id"];

    $tempResult = Comment::getCommentById($id);

    if (count($tempResult) == 0){
        OutputManager::outputMsg(404,"Data Error");
    }

    $model = $tempResult[0];

    if ($model->getOwner() != $ownerId){
        OutputManager::outputMsg(403,"Denied");
    }

    $result = Comment::removeCommentById($id);

    if($result){
        OutputManager::outputMsg(200,"OK");
    }else{
        OutputManager::outputMsg(500,"server error");
    }

}
function getAllSelfComments(){

    $id = $_SESSION["user_id"];
    $userModel = UserAccount::getUserAccountModelById($id)[0];

    $commentModels = Comment::getCommentModelsByUserId($id);

    $returnData = array();
    $returnData["ownerId"] = intval($userModel->getId());
    $returnData["ownerNickName"] = $userModel->getNickName();
    $returnData["ownerImage"] = $userModel->getIconImage();
    $temp2 = array();

    foreach ($commentModels as $value){
        $temp = array();
        $temp["commentId"] = intval($value->getId());
        $temp["commentDescription"] = $value->getDescription();
        $temp["commentDate"] =  date("Y年m月d日",intval($value->getTimeStamp()));
        $articleModel = Article::getArticleModelById($value->getArticleId())[0];
        $categoryModel = Category::getCategoryModelById($articleModel->getCategoryId())[0];
        $temp["articleId"] = intval($articleModel->getId());
        $temp["categoryId"] = intval($categoryModel->getId());
        $temp["articleTitle"] = $articleModel->getTitle();
        $temp["categoryTitle"] = $categoryModel->getCategoryName();


        array_push($temp2,$temp);
    }

    $returnData["comments"] = $temp2;

    if (count($temp2) == 0){
        OutputManager::outputMsg(201,"No record");
    }
    OutputManager::outputMsg(200,$returnData);
}
function getCommentByArticleId($data){

    Verify::verifyInputDataInId($data);

    $articleId = $data["p1"];

    $returnData = array();
    $tempComment = Comment::getCommentModelsByArticleId($articleId);
    if (count($tempComment) == 0){
        OutputManager::outputMsg(201,"No record");
    }

    foreach ($tempComment as $model){
        $temp = array();
        $temp["description"] = $model->getDescription();
        $temp["commentId"] = intval($model->getId());
        $temp["time"] =  date("Y年m月d日",intval($model->getTimeStamp()));
        $ownerId = $model->getOwner();
        $tempOwner = UserAccount::getUserAccountModelById($ownerId);
        if (count($tempOwner) == 0){
            OutputManager::outputMsg(500,"Internal Error");
        }
        $tempOwner = $tempOwner[0];
        $temp["ownerId"] = intval($tempOwner->getId());
        $temp["ownerNickName"] = $tempOwner->getNickName();
        $temp["ownerIconImage"] = $tempOwner->getIconImage();

        array_push($returnData,$temp);
    }

    if (count($returnData) == 0){
        OutputManager::outputMsg(201,"No record");
    }

    OutputManager::outputMsg(200,$returnData);

}

//Article History
function getHistoryVersionByArticleId($id)
{

    Verify::verifyInputDataInId($id);

    $id = $id["p1"];

    //prevent sql injection
    $id = intval($id);

    if (!Article::checkArticleExist($id)) {
        OutputManager::outputMsg(404, "Error Data");
    }

    $returnData = array();

    $articleModel = Article::getArticleModelById($id)[0];

    if ($articleModel->getStatus() == "n-del"){
        OutputManager::outputMsg(202,"removed");
    }elseif ($articleModel->getStatus() == "f-del"){
        OutputManager::outputMsg(203,"removed");
    }

    $returnData["articleId"] = $id;
    $returnData["articleTitle"] = $articleModel->getTitle();


    $histories = array();

    $articleHistoryModels = ArticleHistory::getArticleHistoryModelsByArticleId($id);
    foreach ($articleHistoryModels as $value) {
        $temp = array();
        global $config;
        $a =strip_tags($value->getDescription());
        $temp["versionDescription"] = substr(str_replace("\n","",$a),0,intval($config["description_capture_string"]));
        $temp["versionId"] = intval($value->getId());
        $temp["versionTitle"] = $value->getTitle();
        $temp["versionDate"] = date("Y年m月d日", intval($value->getTimeStamp()));
        $temp["commentCount"] = count(Comment::getCommentModelsByArticleId($id));
        $userModel = UserAccount::getUserAccountModelById($value->getModifierId())[0];
        $temp["modifierName"] = $userModel->getNickName();
        $temp["modifierImage"] = $userModel->getIconImage();
        $temp["modifierId"] = intval($userModel->getId());

        array_push($histories, $temp);

    }

    if (count($histories) == 0) {
        OutputManager::outputMsg(201, "No Record");
    }

    $returnData["versions"] = $histories;

    OutputManager::outputMsg(200, $returnData);

}
//get version detail
function getHistoryVersionDetailByHistoryId($id){

    Verify::verifyInputDataInId($id);

    $id = $id["p1"];
    $tempResult = ArticleHistory::getArticleHistoryModelsById($id);

    if (count($tempResult) == 0){
        OutputManager::outputMsg(201,"No Record");
    }

    $historyModel = $tempResult[0];
    $userModel = UserAccount::getUserAccountModelById($historyModel->getModifierId())[0];
    $commentModels = Comment::getCommentModelsByArticleId($historyModel->getArticleId());
    $articleModel = Article::getArticleModelById($historyModel->getArticleId())[0];
    $categoryModel = Category::getCategoryModelById($articleModel->getCategoryId())[0];

    $temp1= array();
    $returnData = array();
    $returnData["articleId"] = $historyModel->getArticleId();
    $returnData["historyId"] = $id;
    $returnData["historyTitle"] = $historyModel->getTitle();
    $returnData["historyDescription"] = $historyModel->getDescription();
    $returnData["categoryTitle"] = $categoryModel->getCategoryName();
    $returnData["componentIds"] = array();
    $returnData["componentsName"] = array();
    $componentIdsArray = explode(",",$historyModel->getComponentIds());
    if (strlen($historyModel->getComponentIds()) == 0){
        $returnData["componentIds"] = array();
        $returnData["componentsName"] = array();
    }else{

        $tempIds = array();
        $tempNames = array();
        foreach ($componentIdsArray as $value){

            $tModel = Component::getComponentModelById($value);
            if (isset($tModel[0])){
                array_push($tempIds,$value);
                array_push($tempNames,$tModel[0]->getNickName() . "." . $tModel[0]->getFileExt());
            }
        }
        $returnData["componentIds"] = $tempIds;
        $returnData["componentsName"] = $tempNames;
    }

    $returnData["historyDate"] = date("Y年m月d日",intval($historyModel->getTimeStamp()));
    $returnData["ownerId"] = intval($userModel->getId());
    $returnData["ownerImage"] = $userModel->getIconImage();
    $returnData["ownerNickName"] = $userModel->getNickName();

    foreach ($commentModels as $value){
        $temp = array();
        $userModel = UserAccount::getUserAccountModelById($value->getOwner())[0];
        $temp["commentOwnerId"] = intval($userModel->getId());
        $temp["commentOwnerIconImage"] = $userModel->getIconImage();
        $temp["commentOwnNickName"] = $userModel->getNickName();
        $temp["commentDescription"] = $value->getDescription();
        $temp["commentDate"] = date("Y年m月d日",intval($value->getTimeStamp()));

        array_push($temp1,$temp);
    }

    $returnData["comments"] = $temp1;
    OutputManager::outputMsg(200,$returnData);

}

//Component
//return an Id
function startUpload($data){

    Verify::verifyInputDataInString($data,3);
    $nickName = $data["p1"];
    $fileExtension = $data["p2"];
    $block = intval($data["p3"]);
    if ($block < 1){
        OutputManager::outputMsg(405,"Error");
    }

    $fileId = FileHelper::createAnUpload($fileExtension,$nickName,$block);

    $returnData = array(
        "fileId"=>$fileId
    );
    OutputManager::outputMsg(200,$returnData);

}
function uploadContent($data){

    //submit to file handler
    Verify::verifyInputDataInString($data,3);
    $index = intval($data["p1"]);

    $fileId = $data["p2"];
    $data = $data["p3"];

    $result = FileHelper::uploadFile($fileId,$data,$index);

    if ($result === 500){


        unset($_SESSION["file_status"][$fileId]);

        OutputManager::outputMsg("501","upload error");

    }else if($result === 200){

        //remove session data
        $fileMsgKey = $fileId."-info";
        $fileNickNameKey = $fileId . "-nick";
        $ext = $_SESSION["file_status"][$fileId][$fileMsgKey];
        $nickName = $_SESSION["file_status"][$fileId][$fileNickNameKey];
        $owner = $_SESSION["user_id"];

        //insert into DB
        $model = new Component();
        $model->setNickName($nickName);
        $model->setId($fileId);
        $model->setFileExt($ext);
        $model->setOwner($owner);
        $result = $model->save2DB();

        if (!$result){
            OutputManager::outputMsg(500,"error");
        }

        //remove session data
        unset($_SESSION["file_status"][$fileId]);
        OutputManager::outputMsg("200","upload success");

    }else if($result){
        OutputManager::outputMsg("205","ok");
    }else{
        OutputManager::outputMsg("406","upload error");
    }

}
function removeComponent($data){

    Verify::verifyInputDataInString($data);

    $fileId = $data["p1"];

    $temp = Component::getComponentModelById($fileId);

    if (count($temp) == 0){
        OutputManager::outputMsg(404,"Error");
    }

    $model = $temp[0];

    $result = FileHelper::removeUploadedFile($model->getId(),$model->getFileExt());


    if ($result){

        $dbResult = Component::removeComponentModelById($fileId);
            if ($dbResult) {
                OutputManager::outputMsg(200, "ok");
            } else {
                OutputManager::outputMsg(500, "error");
            }

    }else{

            OutputManager::outputMsg(500,"error");


    }


}
function downloadFile($data){

    Verify::verifyInputDataInString($data);

    $fileId = $data["p1"];

    $temp = Component::getComponentModelById($fileId);

    if (count($temp) == 0){
        OutputManager::outputMsg(409,"File not found");
    }

    $model = $temp[0];

    ignore_user_abort(true);
    set_time_limit(0); // disable the time limit for this script

    global $config;

    $path = FileHelper::pathFormatter($config["upload_file_path"]); // change the path to fit your websites document structure

    $fullPath = $path . $model->getId() . "." . $model->getFileExt();

    if(!file_exists($fullPath)){

        OutputManager::outputMsg(409,"File not found");
        exit;

    }

    if ($fd = fopen ($fullPath, "rb")) {
        $fsize = filesize($fullPath);
        $ext = $model->getfileExt();
        switch ($ext) {
            case "pdf":
                header("Content-type: application/pdf;text/html; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"".$model->getNickName() . "." . $model->getFileExt() ."\""); // use 'attachment' to force a fil$
                break;
            case "jpg":
                $img = file_get_contents($fullPath);
                header("Content-Type: image/jpg");
                echo $img;
                exit;
                die();
                break;
            case "jpeg":
                $img = file_get_contents($fullPath);
                header("Content-Type: image/jpeg");
                echo $img;
                exit;
                die();
                break;
            case "png":
                $img = file_get_contents($fullPath);
                header("Content-Type: image/png");
                echo $img;
                exit;
                die();
                break;
            case "gif":
                $img = file_get_contents($fullPath);
                header("Content-Type: image/png");
                echo $img;
                exit;
                die();
                break;
            // add more headers for other content types here
            default;
                header("Content-type: application/octet-stream");
                header("Content-Disposition: filename=\"".$model->getNickName() . "."  . $model->getFileExt() ."\"");
                break;
        }

        header("Content-length: ".$fsize);
        header("Cache-control: private"); //use this to open files directly
        ob_start();

        while(!feof($fd)) {
            $buffer = fread($fd, 1024 * 1024 * 1.5);
            echo $buffer;
            ob_flush();
            flush();
        }
        ob_end_clean();
        fclose($fd);
    }


}


/*--------------------------------------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------- Four Part -------------------------------------------------------------*/
/*--------------------------------------------------------------------------------------------------------------------------------------*/

/*
 * There will seperate two sub-parts in this part
 * First sub-part is Core class
 * Second sub-part is Model class
 * */

/*--------------------------- First sub-part --------------------------- */

//connect db class (singleton)
class DBHelper {

    private static $instance = null;
    private $pdo = null;

    private function __construct()
    {
        global $config;
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC //make the default fetch be an associative array
        ];
       $this->pdo = new PDO("mysql:host=" . $config["db_addr"] . ":". $config["db_port"] . ";charset=utf8;dbname=" .$config["db_name"],$config["db_username"],$config["db_password"],$options);

        //unsafe
        //https://stackoverflow.com/questions/134099/are-pdo-prepared-statements-sufficient-to-prevent-sql-injection
        //$this->queryWithSqlAndDataArray("set names utf8");
    }


    public static function shareHelper()
    {
        if (self::$instance == null)
        {
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

//normalize the output
class OutputManager{

    private static function utf8ize( $mixed ) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = self::utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }
        return $mixed;
    }

    public static function outputMsg($statusCode,$data){

        $data = array(
          "status"=>$statusCode,
          "data"=>$data
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(self::utf8ize($data),JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        die();
    }

}

//normalize the input -- need modify
class InputManager{

    private $rawInputString;//it is a json string;
    private $dataArray;

    /**
     * InputManager constructor.
     * @param $rawInputString
     */
    public function __construct()
    {

        global $config;
        if (isset($_GET[$config["download_file_GET_variable_name"]])){

            $this->dataArray = array(
                "action"=>"downloadFile",
                "data"=>array(
                    "p1"=>$_GET[$config["download_file_GET_variable_name"]]
                )
            );

        }else{
            $this->rawInputString = file_get_contents('php://input');
            $this->dataArray = json_decode($this->rawInputString,true);
        }
    }

    public function getRawInput(){
        return json_decode($this->rawInputString, true);
    }

    public function getActionName(){

        if (isset($this->dataArray["action"])){
            return $this->dataArray["action"];
        }else{
            OutputManager::outputMsg(401,"error");
        }

    }

    public function getRequestData(){
        if (isset($this->dataArray["data"])){
            return $this->dataArray["data"];
        }else{
            OutputManager::outputMsg(400,"error");
        }
    }




}

//dispatch action to function
class Dispatcher{

    private array $availableFunctionName;
    private array $allFunctionName;

    /**
     * Dispatcher constructor.
     * need modify
     * control permission here
     */
    public function __construct()
    {

        $this->availableFunctionName = array();

         if (!isset($_SESSION["user_id"])){

            if (isset($_SERVER["LOGON_USER"])){

               $userInfo = explode("\\",$_SERVER["LOGON_USER"]);
               if (!isset($userInfo[1])){
                   OutputManager::outputMsg(407,"No Login");
               }



               $adName = $userInfo[1];
               $domain = $userInfo[0];
               $result = UserAccount::getUserAccountModelByDomainNameAndAdName($domain,$adName);
               if (isset($result[0])){
                   $_SESSION["user_id"] = $result[0]->getId();
               }else{

                   $model = new UserAccount();
                   $model->setAdName($adName);
                   $model->setNickName($adName);
                   $model->setPermission("r");
                   $model->setIconImage("none");
                   $model->setDomainName($domain);
                   $res = $model->save2DB();
                   if ($res){
                       $result = UserAccount::getUserAccountModelByDomainNameAndAdName($domain,$adName);
                       if (isset($result[0])) {
                           $_SESSION["user_id"] = $result[0]->getId();
                       }else{
                           OutputManager::outputMsg(500);
                       }
                   }else{
                       OutputManager::outputMsg(500);
                   }


               }

           }else{
               OutputManager::outputMsg(407,"No Login");
           }

       }

       if (isset($_SESSION["user_id"])){//grant function's permission to user
           //can optimize
           $models = UserAccount::getUserAccountModelById($_SESSION["user_id"]);
           if (isset($models[0])){

               $model = $models[0];
               $permission = $model->getPermission();

               //define permission
               array_push($this->availableFunctionName,"selfStatistic"); //index 0
               array_push($this->availableFunctionName,"getRecentArticle"); //index 1
               array_push($this->availableFunctionName,"getArticle"); //index 2
               array_push($this->availableFunctionName,"getAllCategory");//index 3
               array_push($this->availableFunctionName,"getHistoryVersionByArticleId"); //index4
               array_push($this->availableFunctionName,"getArticlesByCategory");//index 5
               array_push($this->availableFunctionName,"getRubbishBinArticles");//index 6
               array_push($this->availableFunctionName,"getSelfPermission");//index 7
               array_push($this->availableFunctionName,"getAllSelfArticles");//index 8
               array_push($this->availableFunctionName,"getAllSelfComments");//index 9
               array_push($this->availableFunctionName,"getHistoryVersionDetailByHistoryId");//index 10
               array_push($this->availableFunctionName,"search");//index 11
               array_push($this->availableFunctionName,"getAllUserPermission");//index 12
               array_push($this->availableFunctionName,"modifyUserPermission");//index 13
               array_push($this->availableFunctionName,"changeSelfNickName");//index 14
               array_push($this->availableFunctionName,"changeSelfIconImage");//index 15
               array_push($this->availableFunctionName,"addCategory");//index 16;
               array_push($this->availableFunctionName,"modifyCategory");//index 17
               array_push($this->availableFunctionName,"addComment");//index 18
               array_push($this->availableFunctionName,"modifyComment");//index 19
               array_push($this->availableFunctionName,"removeComment");//index 20
               array_push($this->availableFunctionName,"getCommentByArticleId");//index 21
               array_push($this->availableFunctionName,"removeArticleToRubbishBin");//index 22
               array_push($this->availableFunctionName,"recoverFromRubbishBin");//index 23
               array_push($this->availableFunctionName,"completelyRemoveArticle");//index 24
               array_push($this->availableFunctionName,"startUpload");//index 25;
               array_push($this->availableFunctionName,"uploadContent");//index 26
               array_push($this->availableFunctionName,"removeComponent");//index 27
               array_push($this->availableFunctionName,"addArticle");//index 28
               array_push($this->availableFunctionName,"modifyArticle");//index 29
               array_push($this->availableFunctionName,"removeCategory");//index 30
               array_push($this->availableFunctionName,"downloadFile");//index 31
               array_push($this->availableFunctionName,"getSelfIcon");//index 32
               array_push($this->availableFunctionName,"searchUsername");//index 33


               //copy for reference
               $temp = new ArrayObject($this->availableFunctionName);
               $this->allFunctionName = $temp->getArrayCopy();


               //remove the permission which is not allowed using on holding that kind of permission on user.
               if ("r" == $permission){

                   //getAllUserPermission
                   $this->availableFunctionName[12] = "";
                   unset($this->availableFunctionName[12]);
                   //modifyUserPermission
                   $this->availableFunctionName[13] = "";
                   unset($this->availableFunctionName[13]);
                   //addCategory
                   $this->availableFunctionName[16] = "";
                   unset($this->availableFunctionName[16]);
                   //modifyCategory
                   $this->availableFunctionName[17] = "";
                   unset($this->availableFunctionName[17]);
                   //addComment
                   $this->availableFunctionName[18] = "";
                   unset($this->availableFunctionName[18]);
                   //modifyComment
                   $this->availableFunctionName[19] = "";
                   unset($this->availableFunctionName[19]);
                   //removeComment
                   $this->availableFunctionName[20] = "";
                   unset($this->availableFunctionName[20]);
                   //removeArticleToRubbishBin
                   $this->availableFunctionName[22] = "";
                   unset($this->availableFunctionName[22]);
                   //recoverFromRubbishBin
                   $this->availableFunctionName[23] = "";
                   unset($this->availableFunctionName[23]);
                   //completelyRemoveArticle
                   $this->availableFunctionName[24] = "";
                   unset($this->availableFunctionName[24]);
                   //startUpload
                   $this->availableFunctionName[25] = "";
                   unset($this->availableFunctionName[25]);
                   //uploadContent
                   $this->availableFunctionName[26] = "";
                   unset($this->availableFunctionName[26]);
                   //removeComponent
                   $this->availableFunctionName[27] = "";
                   unset($this->availableFunctionName[27]);
                   //addArticle
                   $this->availableFunctionName[28] = "";
                   unset($this->availableFunctionName[28]);
                   //modifyArticle
                   $this->availableFunctionName[29] = "";
                   unset($this->availableFunctionName[29]);
                   //removeCategory
                   $this->availableFunctionName[30] = "";
                   unset($this->availableFunctionName[30]);
                   //searchUsername
                   $this->availableFunctionName[33] = "";
                   unset($this->availableFunctionName[33]);

               }else if("rw" == $permission){

                   //getAllUserPermission
                   $this->availableFunctionName[12] = "";
                   unset($this->availableFunctionName[12]);
                   //modifyUserPermission
                   $this->availableFunctionName[13] = "";
                   unset($this->availableFunctionName[13]);
                   //modifyCategory
                   $this->availableFunctionName[17] = "";
                   unset($this->availableFunctionName[17]);
                   //removeComment
                   $this->availableFunctionName[20] = "";
                   unset($this->availableFunctionName[20]);
                   //removeArticleToRubbishBin
                   $this->availableFunctionName[22] = "";
                   unset($this->availableFunctionName[22]);
                   //completelyRemoveArticle
                   $this->availableFunctionName[24] = "";
                   unset($this->availableFunctionName[24]);
                   //removeComponent
                   $this->availableFunctionName[27] = "";
                   unset($this->availableFunctionName[27]);
                   //removeCategory
                   $this->availableFunctionName[30] = "";
                   unset($this->availableFunctionName[30]);
                   //searchUsername
                   $this->availableFunctionName[33] = "";
                   unset($this->availableFunctionName[33]);

               }else if("rw+" == $permission){

                   //getAllUserPermission
                   $this->availableFunctionName[12] = "";
                   unset($this->availableFunctionName[12]);
                   //modifyUserPermission
                   $this->availableFunctionName[13] = "";
                   unset($this->availableFunctionName[13]);
                   //completelyRemoveArticle
                   $this->availableFunctionName[24] = "";
                   unset($this->availableFunctionName[24]);
                   //removeComponent
                   $this->availableFunctionName[27] = "";
                   unset($this->availableFunctionName[27]);
                   //removeCategory
                   $this->availableFunctionName[30] = "";
                   unset($this->availableFunctionName[30]);
                   //searchUsername
                   $this->availableFunctionName[33] = "";
                   unset($this->availableFunctionName[33]);

               }else if("s" == $permission){

               }else if("x" == $permission){
                   unset($this->availableFunctionName);
                   $this->availableFunctionName = array();
                   array_push($this->availableFunctionName,"getSelfPermission");
               }else{  //prevent error
                   unset($this->availableFunctionName);
                   $this->availableFunctionName = array();
               }


           }else{
               //For no record user
               $this->availableFunctionName = array();
               array_push($this->availableFunctionName,"getSelfPermission");
               $temp = new ArrayObject($this->availableFunctionName);
               $this->allFunctionName = $temp->getArrayCopy();
           }
       }

    }

    //return: mixed
    public function dispatchToFunction($action,$parameters){

        $canPerform = false;
        $existFunction = false;

        //Check this user can perform the function or not
        foreach ($this->availableFunctionName as $functionName){

            if ($functionName == $action){
                $canPerform = true;
                break;
            }
        }

        foreach ($this->allFunctionName as $functionName){
            if ($functionName == $action){
                $existFunction = true;
                break;
            }
        }

        if ($canPerform){
            $action($parameters);
        }else{//direct output message
            if($existFunction){
                OutputManager::outputMsg("403","Error");
            }else{
                OutputManager::outputMsg("408","Error");
            }
        }


    }


}

class FileHelper{

    public static function createAnUpload($ext = "",$nickName,$block){


        $fileName = self::getFileName();
        $checkList = array();

        $fileMsgKey = $fileName."-info";
        $fileBlockKey = $fileName . "-block";
        $fileNickNameKey = $fileName . "-nick";
        $checkList[$fileMsgKey] = $ext;
        $checkList[$fileBlockKey] = intval($block);
        $checkList[$fileNickNameKey] = $nickName;

        for ($i = 0; $i < $block;$i++){
            $key = $fileName."-".$i;
            $value = "none";
            $checkList[$key] = $value;
        }

        $data = null;
        if (isset($_SESSION["file_status"])){
            $data = $_SESSION["file_status"];
        }
        $data[$fileName] = $checkList;
        $_SESSION["file_status"] = $data;

        return $fileName;

    }

    private static function checkUploadFinished($fileId){

        if (!isset($_SESSION["file_status"][$fileId])){
            return false;
        }

        $array = $_SESSION["file_status"][$fileId];
        $count= $array[$fileId."-block"];

        for ($i = 0; $i < $count;$i++){
            $key = $fileId . "-" . $i;
            if ($array[$key] === "none"){
                return false;
            }

        }

        return true;

    }

    public static function uploadFile($fileId,$data,$index){

        global $config;

        if (!isset($_SESSION["file_status"][$fileId])){
            return false;
        }

        $array = $_SESSION["file_status"][$fileId];
        $count = $array[$fileId."-block"];

        if (($count - 1) < $index || $index < 0){
            return false;
        }

        $fileName = md5(uniqid());
        $basePath = self::pathFormatter($config["upload_cache_file_path"]);
        $filePath = $basePath . $fileName;


        $key = $fileId . "-" . $index;
        file_put_contents($filePath,base64_decode($data));
        $array[$key] = $fileName;
        $_SESSION["file_status"][$fileId] = $array;

        if (self::checkUploadFinished($fileId)){
            $result  = self::combineFile($fileId);
            if ($result){
                return 200;
            }else{
                return 500;
            }

        }else{
            return true;
        }

    }

    private static function combineFile($fileId){

        global $config;

        if (!isset($_SESSION["file_status"][$fileId])){
            return false;
        }

        $dataArray = $_SESSION["file_status"][$fileId];
        $blockSize = $dataArray[$fileId."-block"];
        $ext = $dataArray[$fileId."-info"];
        $cacheFilePath = self::pathFormatter($config["upload_cache_file_path"]);
        $realFileName = self::pathFormatter($config["upload_file_path"]).$fileId.".".$ext;


        for ($i = 0;$i < $blockSize;$i++){

            $cacheFileName = $dataArray[$fileId."-".$i];
            $cacheFileResource = $cacheFilePath . $cacheFileName;
            $data = file_get_contents($cacheFileResource);
            file_put_contents($realFileName,$data,FILE_APPEND | LOCK_EX);
            $data = null;
            unlink($cacheFileResource);
        }

        return true;

    }

    private static function getFileName(){
        return md5(uniqid(rand(), true));
    }

    public static function pathFormatter($path){
        //change path separator to linux style
        $basePath = str_replace("\\","/",$path);
        if (substr($basePath,(strlen($basePath) - 1),1) != "/"){
            $basePath = $basePath . "/";
        }
        return $basePath;
    }

    public static function removeUploadedFile($fileId,$ext){
        global $config;
        $fileRes = self::pathFormatter($config["upload_file_path"]) . self::fileCheck($fileId) . "." . self::fileCheck($ext);
        return unlink($fileRes);
    }
    //prevent injection
    private static function fileCheck($fileId){
        $temp = str_replace("\\","",$fileId);
        $temp = str_replace("/","",$temp);
        return $temp;
    }

}

class Verify{

    /*
     * if the data doesn't pass the verification, it will output error json and stop execute.
     * */

    public static function verifyInputDataInId($data,$amount = 1){

        for ($i = 1; $i <= $amount;$i++){

            $key = "p".$i;
            if (!isset($data[$key])){
                OutputManager::outputMsg(405,"Error");
            }

            if (intval($data[$key]) < 1){
                OutputManager::outputMsg(201,"No Record");
            }

        }

    }
    public static function verifyInputDataInString($data,$amount = 1){

        for ($i = 1; $i <= $amount;$i++){

            $key = "p".$i;
            if (!isset($data[$key])){
                OutputManager::outputMsg(405,"Error");
            }

            if (strlen($data[$key]) == 0){
                OutputManager::outputMsg(201,"No Record");
            }

        }

    }

}

/*--------------------------- Second sub-part -------------------------- */

class ArticleHistory{

    private $id;
    private $articleId;
    private $timeStamp;
    private $description;
    private $componentIds;
    private $modifierId;
    private $title;

    public static function getAllArticleHistoryModels(){

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_article_history order by id desc");
        return self::sqlResult2Model($result);

    }

    public static function getArticleHistoryModelsByArticleId($articleId){

        if ($articleId < 1){
            return array();
        }

        $data = array(
            ":article_id"=>$articleId
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_article_history where article_id = :article_id order by id desc",$data);
        return self::sqlResult2Model($result);

    }

    public static function getArticleHistoryModelsById($id){

        if ($id < 1){
            return false;
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_article_history where id = :id",$data);
        return self::sqlResult2Model($result);

    }

    public static function removeArticleHistoryModelsById($id){

        if ($id < 1){
            return false;
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        return $db->executeSqlWithSqlAndDataArray("delete from forum_article_history where id = :id",$data);

    }

    private static function sqlResult2Model($result){

        if ($result == false || $result == null){
            return array();
        }

        $returnData = array();

        foreach ($result as $value){
            $temp = new ArticleHistory();

            if (isset($value["id"])){
                $temp->setId($value["id"]);
            }else{
                $temp->setId(0);
            }

            if (isset($value["article_id"])){
                $temp->setArticleId($value["article_id"]);
            } else{
                $temp->setArticleId(0);
            }

            if (isset($value["time_stamp"])){
                $temp->setTimeStamp($value["time_stamp"]);
            }else{
                $temp->setTimeStamp(0);
            }

            if(isset($value["description"])){
                $temp->setDescription($value["description"]);
            }else{
                $temp->setDescription("");
            }

            if (isset($value["component_ids"])){
                $temp->setComponentIds($value["component_ids"]);
            }else{
                $temp->setComponentIds("0");
            }

            if (isset($value["modifier_id"])){
                $temp->setModifierId($value["modifier_id"]);
            }else{
                $temp->setModifierId(0);
            }

            if (isset($value["title"])){
                $temp->setTitle($value["title"]);
            }else{
                $temp->setTitle("");
            }


            array_push($returnData,$temp);

        }

        return $returnData;

    }

    public function save2DB(){

        $db = DBHelper::shareHelper();
        $data = array(
            ":article_id"=>$this->articleId,
            ":time_stamp"=>$this->timeStamp,
            ":description"=>$this->description,
            ":component_ids"=>$this->componentIds,
            ":modifier_id"=>$this->modifierId,
            ":title"=>$this->title
        );

        if ($this->id != null){

            $data["id"] = $this->id;

            $sql = "update forum_article_history set article_id = :article_id, time_stamp=:time_stamp, description = :description,  component_ids = :component_ids, title = :title, modifier_id = :modifier_id where id = :id";
            return $db->executeSqlWithSqlAndDataArray($sql,$data);

        }else{
            $sql = "insert into forum_article_history values (null, :article_id, :title, :time_stamp, :description, :component_ids, :modifier_id)";
            return $db->executeSqlWithSqlAndDataArray($sql,$data);

        }

    }

    public function removeArticleHistoryModel(){

       return self::removeArticleHistoryModelsById($this->id);

    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getModifierId()
    {
        return $this->modifierId;
    }

    /**
     * @param mixed $modifierId
     */
    public function setModifierId($modifierId): void
    {
        $this->modifierId = $modifierId;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getArticleId()
    {
        return $this->articleId;
    }

    /**
     * @param mixed $articleId
     */
    public function setArticleId($articleId): void
    {
        $this->articleId = $articleId;
    }

    /**
     * @return mixed
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }

    /**
     * @param mixed $timeStamp
     */
    public function setTimeStamp($timeStamp): void
    {
        $this->timeStamp = $timeStamp;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getComponentIds()
    {
        return $this->componentIds;
    }

    /**
     * @param mixed $componentIds
     */
    public function setComponentIds($componentIds): void
    {
        $this->componentIds = $componentIds;
    }

}

class Comment{

    /*
     * Remark:
     * All the record will be ordered by time
     * */

    private $id;
    private $articleId;
    private $description;
    private $timeStamp;
    private $owner;

    public static function getAllCommentModels(){

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_comment");
        return self::sqlResult2Model($result);

    }

    public static function getCommentModelsByArticleId($articleId){

        if ($articleId < 1){
            return $articleId;
        }

        $data = array(
            ":article_id" =>$articleId
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_comment where article_id = :article_id order by id asc",$data);
        return self::sqlResult2Model($result);

    }

    public static function getCommentModelsByUserId($userId){

        if ($userId < 1){
            return array();
        }

        $data = array(
            ":owner"=>$userId
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_comment where owner = :owner",$data);
        return self::sqlResult2Model($result);

    }

    public static function findCommentModelsByKeyword($keyword){

        if (strlen($keyword) == 0){
            return array();
        }

        $keyword = "%".$keyword."%";

        $data = array(
            ":keyword"=>$keyword
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_comment where description like :keyword",$data);
        return self::sqlResult2Model($result);

    }

    public static function getCommentById($id){

        if ($id < 1){
            return array();
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_comment where id = :id",$data);
        return self::sqlResult2Model($result);

    }

    public static function removeCommentById($id){

        if ($id < 0){
            return false;
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        return $db->executeSqlWithSqlAndDataArray("delete from forum_comment where id = :id",$data);

    }

    private static function sqlResult2Model($result){

        if($result == false || $result == null){
            return array();
        }

        $returnData = array();

        foreach ($result as $value){

            $temp = new Comment();

            if (isset($value["id"])){
                $temp->setId($value["id"]);
            }else{
                $temp->setId(0);
            }

            if (isset($value["article_id"])){
                $temp->setArticleId($value["article_id"]);
            }else{
                $temp->setArticleId(0);
            }

            if (isset($value["description"])){
                $temp->setDescription($value["description"]);
            }else{
                $temp->setDescription("");
            }

            if (isset($value["owner"])){
                $temp->setOwner($value["owner"]);
            }else{
                $temp->setOwner(0);
            }

            if (isset($value["time_stamp"])){
                $temp->setTimeStamp($value["time_stamp"]);
            }else{
                $temp->setTimeStamp(0);
            }

            array_push($returnData,$temp);

        }
        return $returnData;
    }

    public function removeCommentModel(){

        return self::removeCommentById($this->id);

    }

    public function save2DB(){

        $db = DBHelper::shareHelper();
        $data = array(
            ":article_id"=>$this->articleId,
            ":description"=>$this->description,
            ":time_stamp"=>$this->timeStamp,
            ":owner"=>$this->owner
        );


        if ($this->id != null){

            $data[":id"] = $this->id;

            $sql = "update forum_comment set article_id = :article_id, description = :description, time_stamp = :time_stamp, owner = :owner where id = :id";
            return $db->executeSqlWithSqlAndDataArray($sql,$data);

        }else{

            $sql = "insert into forum_comment values(null,:article_id, :description, :time_stamp, :owner)";
            return $db->executeSqlWithSqlAndDataArray($sql,$data);

        }

    }



    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getArticleId()
    {
        return $this->articleId;
    }

    /**
     * @param mixed $articleId
     */
    public function setArticleId($articleId): void
    {
        $this->articleId = $articleId;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }

    /**
     * @param mixed $timeStamp
     */
    public function setTimeStamp($timeStamp): void
    {
        $this->timeStamp = $timeStamp;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner): void
    {
        $this->owner = $owner;
    }


}

class Component{

    private $id;
    private $fileExt;
    private $owner;
    private $nickName;


    public static function getAllComponentModels(){

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_component");
        return self::sqlResult2Model($result);

    }

    public static function getComponentModelById($id){

        if (strlen($id) == 0){
            return array();
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_component where id = :id",$data);
        return self::sqlResult2Model($result);

    }

    public static function getComponentModelByUserId($userId){

        if ($userId < 1){
            return array();
        }

        $data = array(
            ":owner"=>$userId
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_component where owner = :owner",$data);
        return self::sqlResult2Model($result);

    }

    public static function removeComponentModelById($id){
        if (strlen($id) == 0){
            return false;
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        return $db->executeSqlWithSqlAndDataArray("delete from forum_component where id = :id",$data);

    }

    public function removeComponentModel(){

        return self::removeComponentModelById($this->id);

    }

    private static function sqlResult2Model($result){

        if ($result == false || $result == null){
            return array();
        }

        $returnData = array();

        foreach ($result as $value){
            $temp = new Component();
            if (isset($value["id"])){
                $temp->setId($value["id"]);
            }else{
                $temp->setId(0);
            }

            if (isset($value["file_ext"])){
                $temp->setFileExt($value["file_ext"]);
            }else{
                $temp->setFileExt("");
            }

            if (isset($value["owner"])){
                $temp->setOwner($value["owner"]);
            }else{
                $temp->setOwner("");
            }

            if (isset($value["nick_name"])){
                $temp->setNickName($value["nick_name"]);
            }else{
                $temp->setNickName("");
            }

            array_push($returnData,$temp);
        }

        return $returnData;

    }

    public function save2DB(){

        if ($this->id == null){
            return false;
        }

        $tempArray = array(
            ":id"=>$this->id
        );

        $data = array(
            ":id"=>$this->id,
            ":file_ext"=>$this->fileExt,
            ":owner"=>$this->owner,
            ":nick_name"=>$this->nickName
        );

        $testSql = "select * from forum_component where id = :id";
        $db = DBHelper::shareHelper();
        $testResult = $db->queryWithSqlAndDataArray($testSql,$tempArray);

        $data[":file_ext"] = $this->fileExt;
        $data[":owner"] = $this->owner;
        $data[":nick_name"] = $this->nickName;

        //if the database dose not has record, it will add to database. Otherwise, it will modify the record.
        if (count($testResult) == 0){

            $sql = "insert into forum_component values(:id, :file_ext, :owner, :nick_name)";
            return $db->executeSqlWithSqlAndDataArray($sql,$data);

        }else{

            $sql = "update forum_component set file_ext = :file_ext, owner = :owner, nick_name = :nick_name where id = :id";
            return  $db->executeSqlWithSqlAndDataArray($sql,$data);

        }

    }



    /**
     * @return mixed
     */
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * @param mixed $nickName
     */
    public function setNickName($nickName): void
    {
        $this->nickName = $nickName;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getFileExt()
    {
        return $this->fileExt;
    }

    /**
     * @param mixed $fileExt
     */
    public function setFileExt($fileExt): void
    {
        $this->fileExt = $fileExt;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner): void
    {
        $this->owner = $owner;
    }

}

class Category{

    private $id;
    private $category_name;

    public static function checkCategoryExist($categoryId){
        if ($categoryId < 1){
            return false;
        }

        $result = self::getCategoryModelById($categoryId);

        if (count($result) > 0){
            return true;
        }else{
            return false;
        }

    }

    public static function getAllCategoryModels(){
        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_category");
        return self::sqlResult2Model($result);
    }

    public static function findCategoryModelsByKeyword($keyword){

        if (strlen($keyword) < 1){
            return array();
        }

        $keyword = "%" .$keyword . "%";

        $data = array(
            ":keyText"=>$keyword
        );

        $db = DBHelper::shareHelper();
        $sql = "select * from forum_category where category_name like :keyText";
        $result = $db->queryWithSqlAndDataArray($sql,$data);

        return  self::sqlResult2Model($result);

    }

    public static function getCategoryModelById($id){

        if ($id < 1){
            return array();
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_category where id = :id",$data);
        return self::sqlResult2Model($result);


    }

    public static function removeCategoryModelById($id){

        if($id < 1){
            return false;
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        return $db->executeSqlWithSqlAndDataArray("delete from forum_category where id = :id",$data);

    }

    private static function sqlResult2Model($result){

        if ($result == false || $result == null){
            return array();
        }

        $returnData = array();

        foreach ($result as $value){

            $temp = new Category();

            if (isset($value["id"])){
                $temp->setId($value["id"]);
            }else{
                $temp->setId("");
            }

            if (isset($value["category_name"])){
                $temp->setCategoryName($value["category_name"]);
            }else{
                $temp->setCategoryName("");
            }

            array_push($returnData,$temp);

        }

        return $returnData;


    }

    public function remvoeCategoryModel(){
        return self::removeCategoryModelById($this->id);
    }

    public function save2DB(){

        $data = array(
          ":category_name"=>$this->category_name
        );

        if ($this->id != null){

            $data["id"] = $this->id;

            $db = DBHelper::shareHelper();
           return $db->executeSqlWithSqlAndDataArray("update forum_category set category_name = :category_name where id = :id",$data);

        }else{

            $db = DBHelper::shareHelper();
           return $db->executeSqlWithSqlAndDataArray("insert into forum_category values(null,:category_name)",$data);

        }

    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * @param mixed $category_name
     */
    public function setCategoryName($category_name): void
    {
        $this->category_name = $category_name;
    }


}

class UserAccount{

    private $id;
    private $nickName;
    private $adName;
    private $domainName;
    private $iconImage;
    private $permission;

    public static function getAllUserAccountModels(){

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_user_account");
        return self::sqlResult2Model($result);

    }


    public static function getUserAccountModelsByPermission($permission){

        if (!($permission == "s" || $permission == "rw+" || $permission == "rw" || $permission == "x" || $permission == "r")){
            return array();
        }

        $data = array(
            ":permission"=>$permission
        );

        $db = DBHelper::shareHelper();
        $result =  $db->queryWithSqlAndDataArray("select * from forum_user_account where permission = :permission",$data);
        return self::sqlResult2Model($result);

    }
    public static function getUserAccountModelById($id){

        if ($id < 1){
            return array();
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_user_account where id = :id",$data);
        return self::sqlResult2Model($result);

    }
    public static function getUserAccountModelByDomainNameAndAdName($domainName,$adName){

        if (strlen($domainName) < 1 || strlen($adName) < 1){
            return array();
        }

        $data = array(
            ":domain_name"=>$domainName,
            ":ad_name"=>$adName
        );

        $db = DBHelper::shareHelper();

        $result = $db->queryWithSqlAndDataArray("select * from forum_user_account where ad_name = :ad_name and domain_name = :domain_name",$data);
        return self::sqlResult2Model($result);

    }
    public static function getUserAccountModelByKeyword($keyword){

        if ($keyword == null){
            return array();
        }

        if (strlen($keyword) == 0){
            return array();
        }

        $keyword = "%" . $keyword . "%";

        $db = DBHelper::shareHelper();
        $data = array(
            ":keyword"=>$keyword
        );

        $sql = "select * from forum_user_account where (nick_name like :keyword or ad_name like :keyword)";
        $sqlResult = $db->queryWithSqlAndDataArray($sql,$data);

        return self::sqlResult2Model($sqlResult);


    }

    private static function sqlResult2Model($result){

        if ($result == null || $result == false) {
            return array();
        }

        $returnData = array();

        foreach ($result as $value){
            $model = new UserAccount();

            if (isset($value["id"])){
                $model->setId($value["id"]);
            }else{
                $model->setId(0);
            }

            if (isset($value["nick_name"])){
                $model->setNickName($value["nick_name"]);
            }else{
                $model->setNickName("");
            }

            if (isset($value["ad_name"])){
                $model->setAdName($value["ad_name"]);
            }else{
                $model->setAdName("");
            }

            if (isset($value["domain_name"])){
                $model->setDomainName($value["domain_name"]);
            }else{
                $model->setDomainName("");
            }

            if (isset($value["icon_image"])){
                $model->setIconImage($value["icon_image"]);
            }else{
                $model->setIconImage("");
            }

            if (isset($value["permission"])){
                $model->setPermission($value["permission"]);
            }else{
                $model->setPermission("x");
            }


            array_push($returnData,$model);
        }

        return $returnData;


    }

    public function save2DB(){

        $db = DBHelper::shareHelper();
        $data = array(
            ":nick_name"=>$this->nickName,
            "ad_name"=>$this->adName,
            ":domain_name"=>$this->domainName,
            ":icon_image"=>$this->iconImage,
            ":permission"=>$this->permission
        );

        if ($this->id != null){

            $data["id"] = $this->id;

            $sql = "update forum_user_account set nick_name = :nick_name, ad_name = :ad_name, domain_name = :domain_name, icon_image = :icon_image, permission = :permission where id = :id";
            return $db->executeSqlWithSqlAndDataArray($sql,$data);

        }else{

            $sql = "insert into forum_user_account values(null ,:nick_name, :ad_name, :domain_name, :icon_image, :permission)";
            return $db->executeSqlWithSqlAndDataArray($sql,$data);

        }

    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * @param mixed $nickName
     */
    public function setNickName($nickName): void
    {
        $this->nickName = $nickName;
    }

    /**
     * @return mixed
     */
    public function getAdName()
    {
        return $this->adName;
    }

    /**
     * @param mixed $adName
     */
    public function setAdName($adName): void
    {
        $this->adName = $adName;
    }

    /**
     * @return mixed
     */
    public function getDomainName()
    {
        return $this->domainName;
    }

    /**
     * @param mixed $domainName
     */
    public function setDomainName($domainName): void
    {
        $this->domainName = $domainName;
    }

    /**
     * @return mixed
     */
    public function getIconImage()
    {
        return $this->iconImage;
    }

    /**
     * @param mixed $iconImage
     */
    public function setIconImage($iconImage): void
    {
        $this->iconImage = $iconImage;
    }

    /**
     * @return mixed
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @param mixed $permission
     */
    public function setPermission($permission): void
    {
        $this->permission = $permission;
    }

}

class Article{

    //Remark:
    //it will only return status = no-del article except function(getArticleFromRecycleBin)
    //the article models return list is order by time stamp
    //
    private $id;
    private $componentIds;
    private $title;
    private $description;
    private $owner;
    private $timeStamp;
    private $categoryId;
    private $status;
	
	public static function getAllArticle(){
		
        $db = DBHelper::shareHelper();
		
        $result = $db->queryWithSqlAndDataArray("select * from forum_article");
		
        return self::sqlResult2Model($result);
		
    }

    public static function checkArticleExist($id){

        if ($id < 1){
            return false;
        }

        $data = self::getArticleModelById($id);

        if (count($data) > 0){
           $model =  $data[0];

           if($model->getStatus() == "no-del"){
               return true;
           }else{
               return false;
           }

        }else{
            return false;
        }
    }

    public static function getRecentAddArticleModels(){

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_article where status ='no-del' order by id desc limit 10");
        return self::sqlResult2Model($result);

    }

    public static function getAllArticleModelsBaseOnCategoryId($categoryId,$ignoreDel = true){

        if ($categoryId < 1){
            return array();
        }

        $data = array(
            ":category_id"=>$categoryId
        );

        $db = DBHelper::shareHelper();
        $sql = "";
        if ($ignoreDel){
            $sql = "select * from forum_article where category_id = :category_id and status = 'no-del' order by id desc";
        }else{
            $sql = "select * from forum_article where category_id = :category_id order by id desc";
        }

        $result = $db->queryWithSqlAndDataArray($sql,$data);

        return self::sqlResult2Model($result);

    }

    public static function findArticleModelsByKeyword($keyword){

        if (strlen($keyword) < 1){
            return array();
        }

        $keyword = "%" . $keyword . "%";

        //Filter
        // you can add more to get ideal result
        $data = array(
            ":titleKey"=>$keyword,
            ":descriptionKey"=>$keyword
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_article where (title like :titleKey or description like :descriptionKey) and status = 'no-del' order by id desc",$data);

        return self::sqlResult2Model($result);

    }

    public static function getAllArticleModelsFromRecycleBin(){

        $db = DBHelper::shareHelper();
        $data = array(
            ":status"=>"n-del"
        );
        $result = $db->queryWithSqlAndDataArray("select * from forum_article where status = :status order by id desc",$data);

        return self::sqlResult2Model($result);

    }

    public static function getAllArticleModelsFromTimeStamp($timeStamp){
        if ($timeStamp < 0){
            return array();
        }

        $data = array(
            ":time_stamp"=>$timeStamp
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_article where time_stamp = :time_stamp and status = 'no-del' order by id desc",$data);

        return self::sqlResult2Model($result);

    }

    public static function removeArticleRecordByArticleId($id){

        if ($id < 1){
            return false;
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
         return $db->executeSqlWithSqlAndDataArray("delete from forum_article where id = :id;",$data);

    }

    public static function getAllArticleModelsByUserId($user_id)
    {
        if ($user_id < 1) {
            return array();
        }

        $data = array(
            ":owner"=>$user_id
        );

        $db = DBHelper::shareHelper();

        $result = $db->queryWithSqlAndDataArray("select * from forum_article where owner = :owner and status = 'no-del' order by id desc",$data);

        return self::sqlResult2Model($result);

    }

    public static function getArticleModelById($id){

        if ($id < 1){
            return array();
        }

        $data = array(
            ":id"=>$id
        );

        $db = DBHelper::shareHelper();
        $result = $db->queryWithSqlAndDataArray("select * from forum_article where id = :id",$data);

        return self::sqlResult2Model($result);

    }

    //if ok will return true, not ok return fales
    public function save2DB(){

        $data = array(
            ":component_ids"=>$this->componentIds,
            ":title"=>$this->title,
            ":description"=>$this->description,
            ":owner"=>$this->owner,
            ":time_stamp"=>$this->timeStamp,
            ":category_id"=>$this->categoryId,
            ":status"=>$this->status
        );
        $db = DBHelper::shareHelper();

        if ($this->id != null){

            if (intval($this->id) < 1){
                return  false;
            }

            $data[":id"] = $this->id;
            return $db->executeSqlWithSqlAndDataArray("update forum_article set component_ids = :component_ids, title = :title, description = :description, owner = :owner, time_stamp = :time_stamp, category_id = :category_id, status=:status where id = :id ",$data);
        }else{
            return $db->executeSqlWithSqlAndDataArray("insert into forum_article values(null,:component_ids,:title,:description,:owner,:time_stamp,:category_id,:status)",$data);
        }

    }

    public function move2RecycleBin(){
        if ($this->id == null){
            return false;
        }

        $data = array(
            ":id"=>$this->id
        );

        $db = DBHelper::shareHelper();
        return $db->executeSqlWithSqlAndDataArray("update forum_article set status = 'n-del' where id = :id",$data);

    }

    public function recoverFromRecycleBin(){

        if ($this->id == null){
            return false;
        }

        $data = array(
            ":id"=>$this->id
        );

        $db = DBHelper::shareHelper();
        return $db->executeSqlWithSqlAndDataArray("update forum_article set status = 'no-del' where id = :id",$data);
    }

    public function completelyRemove(){
        if ($this->id == null){
            return false;
        }

        $data = array(
            ":id"=>$this->id
        );

        $db = DBHelper::shareHelper();
        return $db->executeSqlWithSqlAndDataArray("update forum_article set status = 'f-del' where id = :id",$data);
    }

    //translate the sql result to model
    private static function sqlResult2Model($result){

        $returnData = array();

        if ($result == null || $result == false){
            return $returnData;
        }

        foreach ($result as $value){
            $model = new Article();
            if (isset($value["id"])){
                $model->setId($value["id"]);
            }else{
                $model->setId(0);
            }

            if (isset($value["component_ids"])){
                $model->setComponentIds($value["component_ids"]);
            }else{
                $model->setComponentIds("none");
            }

            if (isset($value["title"])){
                $model->setTitle($value["title"]);
            }else{
                $model->setTitle("");
            }

            if (isset($value["description"])){
                $model->setDescription($value["description"]);
            }else{
                $model->setDescription("");
            }

            if (isset($value["owner"])){
                $model->setOwner($value["owner"]);
            }else{
                $model->setOwner(0);
            }

            if (isset($value["time_stamp"])){
                $model->setTimeStamp($value["time_stamp"]);
            }else{
                $model->setTimeStamp(0);
            }

            if (isset($value["category_id"])){
                $model->setCategoryId($value["category_id"]);
            }else{
                $model->setCategoryId(0);
            }

            if (isset($value["status"])){
                $model->setStatus($value["status"]);
            }else{
                $model->setStatus("");
            }
            array_push($returnData,$model);
        }

        return $returnData;

    }


    /*
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getComponentIds()
    {
        return $this->componentIds;
    }

    /**
     * @param mixed $componentIds
     */
    public function setComponentIds($componentIds): void
    {
        $this->componentIds = $componentIds;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return mixed
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }

    /**
     * @param mixed $timeStamp
     */
    public function setTimeStamp($timeStamp): void
    {
        $this->timeStamp = $timeStamp;
    }



    /**
     * @return mixed
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param mixed $categoryId
     */
    public function setCategoryId($categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }




}


































































//---------------------------------------