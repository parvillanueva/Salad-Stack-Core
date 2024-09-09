<?php

namespace Salad\Core;

use Salad\Core\Application;

class Response
{
	public function redirect($url)
	{
		header("Location: $url");
	}

}