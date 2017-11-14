API Design Training Example Project
===================================

Build a simple conference API as an exercise in API design.

Provided as a starting point:

- The speaker / talk data.
- A data service interface used to access the data.
- A read-only implementation of the interface (should work).
- A write implementation of the interface (not complete yet).

Docker
------

A simple `docker-compose` configuration provides a php development server, and a
utility for running composer.

To run composer, use:

    docker-compose run --rm composer [command]

_`composer dump-autoload` should be used to setup autoloading for the data
service._

To run the php development service:

    docker-compose up php

By default, it expects the gateway script to be at `public/index.php`.

