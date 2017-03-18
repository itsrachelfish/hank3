<?php
declare(strict_types=1);

class Module_ChildLock implements Module {
    function help(): array {
        return [];
    }

    function init($hank) {
        $hank->registerCatchall([ $this, 'maybeBlockChild' ]);
    }

    function maybeBlockChild($c) {
        if (strpos($c->nick_host, 'hexa') !== false) {
            return false;
        }
    }
}
