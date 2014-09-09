CurlParallel
============

This is a fork, or derivate work of James Socol OOCurl and OOCurlParallel classes.
It just change some methods (adding here and there), define a Sender class and iSenderConsumer.

Plain classes, examples and PHPUnit test coming soon.

I could have brached from here https://github.com/jsocol/oocurl , but I have a different plan on the use
in pseudo parallel php pattern

MIT License as original work.

How much parallel
==

http://libevent.org/ and php extention http://php.net/manual/it/book.libevent.php permit to define event loop as it work on nodejs: single process but no blocking tasks. I doubt it is the right choice, php infrastructure is not born for this. Tough PHP language by itself could be used for it (i.e. with CLI), it is not so easy to use some extention that was intended for use on apache server (or through fcgi, or php standalone server). However here is the same concept used only for one kind of I/O, it does not need libevent but in fact it works the same way, no fork, just no blocking I/O and raise event on data
