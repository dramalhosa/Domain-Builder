<?php
class Domains extends Controller{

  public function __construct(){
    $this->selectModel = $this->model('Domain');
  }

  public function create() {
    $request[0] = "accessToken";
    $request[1] = "access";
    $info = check($request);

    $data = [
      'title' => 'Create A Domain',
      'access' => $info->access,
      'description' => 'Build a new domain from scratch'
    ];

    $list->reseller = $this->selectModel->resellerList($info);
    $list->dialPlan = $this->selectModel->dialPlanList($info);
    $list->domain = $this->selectModel->domainList($info);
    //Add API Manager & E911 Notify

    if($_POST['submit'] == "validate"){
      //Process form
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

      $data['domainCreate'] = $this->selectModel->validate($list);
    }
    if($_POST['submit'] == "submit"){
      $this->selectModel->buildDomain($info, $list);
    }

    $this->view('domains/create', $data);
  }

}
