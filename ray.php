<?php

return [
    /*
    * This settings controls whether data should be sent to Ray.
    */
    'enable' => getenv('RAY_ENABLED') ?: false,
    /*
     *  The host used to communicate with the Ray app.
     */
    'host' => '127.0.0.1',

    /*
     *  The port number used to communicate with the Ray app.
     */
    'port' => getenv('RAY_PORT') ?: 8001,

    /*
     *  Absolute base path for your sites or projects in Homestead, Vagrant, Docker, or another remote development server.
     */
    'remote_path' => null,

    /*
     *  Absolute base path for your sites or projects on your local computer where your IDE or code editor is running on.
     */
    'local_path' => null,

    /*
     * When this setting is enabled, the package will not try to format values sent to Ray.
     */
    'always_send_raw_values' => false,
];
