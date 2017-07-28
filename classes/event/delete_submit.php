<?php

namespace mod_apply\event;


defined('MOODLE_INTERNAL') || die();


class delete_submit extends \core\event\base
{
	public static function get_name()		// イベント名
	{
		return 'delete';
	}


	public function get_url()
	{
		$params = array();
		if (isset($this->other['params'])) $params = $this->other['params'];
		if (!is_array($params)) $params = array();

		$params = array_merge(array('course' => $this->courseid), $params);
		return new \moodle_url('/mod/apply/delete_submit.php', $params);
	}


	public function get_description()
	{
		$info = '';
		if (isset($this->other['info'])) $info = $this->other['info'];

		return $info;
	}


	protected function init()
	{
		$this->data['crud'] = 'd'; 						// イベントの種類　c(reate), r(ead), u(pdate), d(elete)
		$this->data['edulevel'] = self::LEVEL_OTHER; 	// 教育レベル LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER 
	}
}
