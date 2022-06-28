<?php defined('BASEPATH') or exit('No direct script access allowed');


class Toastr
{

    
    protected $CI;

   

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('session');
    }

    

    public function success($msg)
    {
        // Flash message to the session
        $this->CI->session->set_flashdata('success', $msg);
        // returns response when session is successfully set
        return true;
    }

    

    public function error($msg)
    {
        // Flash message to the session
        $this->CI->session->set_flashdata('error', $msg);
        // returns response when session is successfully set
        return true;
    }

    

    public function warning($msg)
    {
        // Flash message to the session
        $this->CI->session->set_flashdata('warning', $msg);
        // returns response when session is successfully set
        return true;
    }

    
    public function info($msg)
    {
        // Flash message to the session
        $this->CI->session->set_flashdata('info', $msg);
        // returns response when session is successfully set
        return true;
    }
}
