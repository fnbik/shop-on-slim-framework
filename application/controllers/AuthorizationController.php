<?php
use App\Models\User;
use Respect\Validation\Validator as v;
include_once dirname(__DIR__)."\models\DB connection.php";
class AuthorizationController
{
    public $m_twig_container;
    public $m_nav_container;
    public $m_val_con;
    public $db;
    public $csrf;
    public $auth;
    public $capsule;
    function __construct($container)
    {
        $this->m_twig_container = $container['twig_c'];
        $this->m_nav_container = $container['nav_bar'];
        $this->m_val_con = $container['validator'];
        $this->csrf = $container['csrf'];
        $this->auth = $container['auth'];
        $this->capsule = $container['db'];
        $this->db = connection::getInstance();
    }
    public function getSignUp($req, $resp, $arg)
    {
        $template = $this->m_twig_container->loadTemplate('SignUp.html');
        $catalogName = $this->db->getConnection()->catalog;
        echo $template->render(
            array(
                'title' => 'SignUp',
                'nav_list' => $this->m_nav_container,
                'catalog_buff' => $catalogName,
                'EE' => $req->getParam('EE'),
                'NE' => $req->getParam('NE'),
                'PE' => $req->getParam('PE')
            ));
        return $resp;
    }
    public function postSignUp($req, $resp, $arg)
    {
        $validation = $this->m_val_con->validate($req,
            [
                'Email' => v::notEmpty()->email(),
                'Name' => v::notEmpty(),
                'Password' => v::notEmpty()
            ]);

        $isExist = $this->capsule->table('users')->where('Email', $req->getParam('Email'))->exists();
        if($isExist)
        {
            $this->m_val_con->setError('Such mail already exists');
            return $resp->withRedirect('/SignUp?EE='. $this->m_val_con->getErrors()['Email'][0]);
        }
        if($this->m_val_con->failed())
        {
            return $resp->withRedirect('/SignUp?EE='. $this->m_val_con->getErrors()['Email'][0]
                                             .'&NE='. $this->m_val_con->getErrors()['Name'][0]
                                             .'&PE='. $this->m_val_con->getErrors()['Password'][0]);
        }

        User::create(
            [
                'Fname' => $req->getParam('Name'),
                'Email' => $req->getParam('Email'),
                'Password' => password_hash($req->getParam('Password'), PASSWORD_DEFAULT, ['cost' => 12,]),
                'Role' => 'member'
            ]
        );

        $this->auth->attempt($req->getParam('Email'), $req->getParam('Password'));

        return $resp->withRedirect('/');
    }
    public function getSignIn($req, $resp, $arg)
    {
        $template = $this->m_twig_container->loadTemplate('SignIn.html');
        $catalogName = $this->db->getConnection()->catalog;
        echo $template->render(
            array(
                'title' => 'SignIn',
                'nav_list' => $this->m_nav_container,
                'catalog_buff' => $catalogName
            )
        );
        return $resp;
    }
    public  function postSignIn($req, $resp, $arg)
    {
        $auth = $this->auth->attempt(
            $req->getParam('Email'),
            $req->getParam('Password')
        );
        if(!$auth)
            return $resp->withRedirect('/SignIn');
        return $resp->withRedirect('/');
    }
    public function getSignOut($req, $resp, $arg)
    {
        $this->auth->logout();
        return $resp->withRedirect('/');
    }
}