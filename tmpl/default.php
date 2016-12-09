<?php defined('_JEXEC') or die;

$output = [];

foreach( $list as $index => $item )
{
	$index += 1;

	$output[] = '<div class="panel panel-default panel-djunked">';
	$output[] = 	'<div class="panel-heading" role="tab" id="q' . $index . '">';
	$output[] = 		'<h3 class="panel-title"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>';
	$output[] = 			'<a role="button" data-toggle="collapse" data-parent="#accordion"';
	$output[] = 			'href="#answer_q' . $index . '" aria-expanded="false" aria-controls="answer_q' . $index . '">' . $item->title . '</a>';
	$output[] = 		'</h3>';
	$output[] = 	'</div>';
	$output[] = '</div>';
	$output[] = '<div id="answer_q' . $index . '" class="panel-collapse collapse out" role="tabpanel" aria-labelledby="q' . $index . '">';
	$output[] = 	'<div class="panel-body">';
	$output[] = 		$item->displayIntrotext;
	$output[] = 	'</div>';
	$output[] = '</div>';
}

echo implode($output);