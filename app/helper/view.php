<?php
namespace App\Helper;

class View {
	public function job_img($team_member) {
		return ($team_member['character_gender'] == 1) ? $team_member['image_boy'] : $team_member['image_girl'];
	}
}
