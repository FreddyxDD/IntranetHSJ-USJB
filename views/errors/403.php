<?php

declare(strict_types=1);

http_response_code(403);
echo view('errors.403')->render();
