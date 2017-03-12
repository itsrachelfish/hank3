<?php
declare(strict_types=1);

interface Module {
    function help();
    function init($hank);
}
