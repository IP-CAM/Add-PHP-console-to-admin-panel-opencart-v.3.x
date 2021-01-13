<?php
class ControllerToolConsole extends Model {
    private $error = array();

    public function index() {
        $this->load->language('tool/console');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addStyle('view/javascript/codemirror/lib/codemirror.css');
        $this->document->addStyle('view/javascript/codemirror/theme/monokai.css');
        $this->document->addScript('view/javascript/codemirror/lib/codemirror.js');
        $this->document->addScript('view/javascript/console/codemirror/php/php.js');
        $this->document->addScript('view/javascript/console/codemirror/clike/clike.js');

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];

            unset($this->session->data['error']);
        } elseif (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (!empty($this->session->data['Console']['php_code'])) {
            $data['php_code'] = $this->session->data['Console']['php_code'];
        } else {
            $data['php_code'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('tool/console', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['download'] = $this->url->link('tool/console/download', 'user_token=' . $this->session->data['user_token'], true);
        $data['clear'] = $this->url->link('tool/console/clear', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('tool/console', $data));
    }

    public function process() {

        if (!empty($this->request->post['show_errors'])) {
            ini_set("display_errors", 1);
        }

        $code = $this->request->post['code'];
        $this->session->data['Console']['php_code'] = $code;

        $code = preg_replace('/^ *(<\?php|<\?)/mi', '', $code);
        $code = html_entity_decode($code);

        ob_start();

        $prevMem = memory_get_peak_usage(true);
        $timestart = microtime(true);

        $result = eval($code);

        $totalTime = (microtime(true) - $timestart);
        $totalMem = round((memory_get_peak_usage(true) - $prevMem) / 1048576, 2);
        $output = ob_get_contents();
        
        ob_end_clean();

        if ($result) {
            $output = $result;
        }

        $completed = true;

        if (isset($this->session->data['Console']['php_completed'])) {
            if ($this->session->data['Console']['php_completed'] === false) {
                $completed = false;
            } else {
                unset($this->session->data['Console']['php_completed']);
            }
        }
        
        $wrapper = "<pre>";
        $wrapper .= print_r($output,true);
        $wrapper .= "</pre>";
        
        $output = $wrapper;

        if (!empty($this->request->post['show_report'])) {

            $report = "<hr />\n";
            $report .= "<pre>\n";
            $report .= "Total time: " . sprintf("%2.4f s", $totalTime);
            $report .= "\nMemory: " . $totalMem . " MB\n";
            $report .= "</pre>";

            $output .= $report;
        }

        $json  = array(
            'completed' => $completed,
            'output' => $output
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function column_left() {

        $this->load->language('tool/console');

        if ($this->user->hasPermission('access', 'tool/console')) {
            return array(
                'name'       => $this->language->get('heading_title'),
                'href'     => $this->url->link('tool/console', 'user_token=' . $this->session->data['user_token'], true),
                'children' => array()
            );
        }
    }
}
