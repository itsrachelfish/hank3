all: strings

strings:
	(./bin/make_strings.sh | tee strings.php) && php -l strings.php

.PHONY: all strings
