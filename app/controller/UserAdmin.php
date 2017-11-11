<?php 
/**
 * 管理员账户-控制器
 * author：yzs
 * create：2017.8.15
 */
namespace app\controller;

use app\model\MyException;

class UserAdmin extends Common{
	/**
	 * 后台登录
	 */
	public function login(){
		$data = input('post.');
		if(!empty($data)){
			$ret = ['error_code' => 0, 'msg' => '登陆成功'];
			try{
				D('UserAdmin')->dologin($data);
                $log['user_id'] = $this->getUserId();
                $log['IP'] = $this->getUserIp();
                $log['section'] = '用户登录／用户退出';
                $log['action_descr'] = '用户登录';
                D('OperationLog')->addData($log);
			}catch(MyException $e){
				$ret['error_code'] = 1;
				$ret['msg'] = $e->getMessage();
			}catch(\Exception $e){
				$ret['error_code'] = 1;
				$ret['msg'] = $e->getMessage();
			}
			$this->jsonReturn($ret);
		}
		return view('', []);
	}


    /**
     * 修改密码
     * @return \think\response\View
     */
    public function changePwd()
    {
        return view('', []);
    }

    /**
     * 用户信息
     * @return \think\response\View
     */
    public function account()
    {
        return view('', []);
    }


    /**
	 * 登出
	 */
	public function dologout(){
		$ret = ['error_code' => 0, 'data' => [], 'msg' => ''];
		try{
			$token = session('token');
			if(!$token) $token = input('request.token');
			if(!$token) throw new MyException('token不能空');
            $log['user_id'] = $this->getUserId();
            $log['IP'] = $this->getUserIp();
            $log['section'] = '用户登录／用户退出';
            $log['action_descr'] = '用户退出';
            D('OperationLog')->addData($log);
            D('UserAdmin')->logout($token);
		}catch(MyException $e){
			$ret['error_code'] = 1;
			$ret['msg'] = $e->getMessage();
		}catch(\Exception $e){
			$ret['error_code'] = 1;
			$ret['msg'] = '系统异常';
			$ret['msg'] = $e->getMessage();
		}
		$this->jsonReturn($ret);
	}
	/**
	 * 管理员列表
	 * @return \think\response\View
	 */
	public function index(){
		$params = input('get.');
		$status = input('get.status');
		$username = input('get.username');
		$cond = [];
		if($status){
			$cond['status'] = $status;
		}
		if($username){
			$cond['username'] = ['like', '%'.$username.'%'];
		}
		$list = D('UserAdmin')->getList($cond);
		return view('', ['list' => $list, 'cond' => $params]);
	}
	/**
	 * 新建管理员账号
	 */
	public function create(){
		$data = input('post.');
		if(!empty($data)){
			$ret = ['error_code' => 0, 'msg' => ''];
			$res = D('UserAdmin')->addData($data);
			if(!$res){
				$ret['error_code'] = 1;
				$ret['msg'] = '创建用户失败';
			}
			$this->jsonReturn($ret);
		}
		$roles = D('Role')->getRoleList();
		return view('', ['roles' => $roles]);
	}
	/**
	 * 编辑账号
	 */
	public function edit(){
		$data = array_filter(input('post.'));
		if(!empty($data)){
			$ret = ['error_code' => 0, 'msg' => ''];
			$res = D('UserAdmin')->saveData($data['id'], $data);
			if(!$res){
				$ret['error_code'] = 1;
				$ret['msg'] = '编辑用户失败';
			}
            $this->jsonReturn($ret);
		}

		$id = input('get.id');
		$data = D('UserAdmin')->getById($id);
		$roles = D('Role')->getRoleList();
		return view('', ['data' => $data, 'roles' => $roles]);
	}
	/**
	 * 批量删除
	 */
	public function remove(){
		$ret = ['code' => 1, 'msg' => '成功'];
		$ids = input('get.ids');
		try{
			$res = D('UserAdmin')->remove(['id' => ['in', $ids]]);
		}catch(MyException $e){
			$ret['code'] = 2;
			$ret['msg'] = '删除失败';
		}
		$this->jsonReturn($ret);
	}
	/**
	 * 权限修改
	 */
	public function authority(){
		$actions = D('Action')->getActions();
		$this->jsonReturn($actions);
	}
	/**
	 * 角色列表
	 */
	public function roles(){
        $list = D('Role')->getList();
		return view('', ['list' => $list]);
	}

	public function getRolesList(){
        $params = input('post.');
        $ret = ['error_code' => 0, 'data' => [], 'msg' => ""];
//        $user_id = $this->getUserId();
        $cond = [];
//        $cond['hospital_id'] = ['=', $user_id];
        $list = D('Role')->getList();
        $page = input('post.current_page',0);
        $per_page = input('post.per_page',0);
        //分页时需要获取记录总数，键值为 total
        $ret["total"] = count($list);
        //根据传递过来的分页偏移量和分页量截取模拟分页 rows 可以根据前端的 dataField 来设置
        $ret["data"] = array_slice($list, ($page-1)*$per_page, $per_page);
        $ret['current_page'] = $page;
        $this->jsonReturn($ret);
    }
	/**
	 * 新建角色
	 */
	public function roleCreate(){
		$data = input('post.');
		if(!empty($data)){
			$ret = ['error_code' => 0, 'msg' => ''];
			$res = D('Role')->addData($data);
			if(!$res){
				$ret['error_code'] = 1;
				$ret['msg'] = '创建角色失败';
			}
			$this->jsonReturn($ret);
		}
		return view('', []);
	}
	/**
	 * 编辑角色
	 */
	public function roleEdit(){
		$data = input('post.');
		if(!empty($data)){
			$ret = ['error_code' => 0, 'msg' => '编辑角色成功'];
            $res = D('Role')->saveData($data['id'], $data);
            $ret['res'] = $res;
			if(!$res){
				$ret['error_code'] = 1;
				$ret['msg'] = '编辑角色失败';
			}
			$this->jsonReturn($ret);
		}
		$role_id = input('get.id');
		$role = D('Role')->getById($role_id);
		return view('', ['role' => $role]);
	}
	/**
	 * 批量删除
	 */
	public function roleRemove(){
		$ret = ['code' => 1, 'msg' => '成功'];
		$ids = input('post.ids');
		try{
			$res = D('Role')->remove(['id' => ['in', $ids]]);
		}catch(MyException $e){
			$ret['code'] = 2;
			$ret['msg'] = '删除失败';
		}
		$this->jsonReturn($ret);
	}

    /**
     * 获取用户名称
     */
	public function getUserName(){
        $user_id = $this->getUserId();
        $ret = ['error_code' => 0, 'msg' => ''];
        $ret['username'] = D('UserAdmin')->getById($user_id)['username'];
        $this->jsonReturn($ret);
    }
}
?>