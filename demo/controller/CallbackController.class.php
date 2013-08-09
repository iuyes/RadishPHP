<?php
class CallbackController extends BaseController implements IController {
	function index() {
		echo '<a href="http://www.demo.com">Enter</a>';
		exit(0);
	}
}