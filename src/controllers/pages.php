<?php 

require_once 'base.php';

class Pages extends Base {
	
	public function view($page = 'frontpage') {
		$data = array();
		$data['title'] = $this->site;
		$data['is_static_page'] = true;
		$templatePath = $this->getProjectTemplatePath("pages/$page");
		if (empty($templatePath)) {
			show_404($this->site);
		} else {
			$this->renderPage("pages/$page", $data);
		}
	}
}


?>