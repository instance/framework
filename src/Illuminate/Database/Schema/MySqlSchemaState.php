<?php

namespace Illuminate\Database\Schema;

class MySqlSchemaState extends SchemaState
{
    /**
     * Dump the database's schema into a file.
     *
     * @param  string  $path
     * @return void
     */
    public function dump($path)
    {
        $this->makeProcess(
            $this->baseDumpCommand().' --routines --result-file=$LARAVEL_LOAD_PATH --no-data'
        )->mustRun($this->output, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));

        $this->removeAutoIncrementingState($path);

        $this->appendMigrationData($path);
    }

    /**
     * Remove the auto-incrementing state from the given schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function removeAutoIncrementingState(string $path)
    {
        $this->files->put($path, preg_replace(
            '/\s+AUTO_INCREMENT=[0-9]+/iu',
            '',
            $this->files->get($path)
        ));
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        with($process = $this->makeProcess(
            $this->baseDumpCommand().' migrations --no-create-info --skip-extended-insert --skip-routines --compact'
        ))->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            //
        ]));

        $this->files->append($path, $process->getOutput());
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $process = $this->makeProcess('mysql --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --user=$LARAVEL_LOAD_USER --password=$LARAVEL_LOAD_PASSWORD --database=$LARAVEL_LOAD_DATABASE < $LARAVEL_LOAD_PATH');

        $process->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Get the base dump command arguments for MySQL as a string.
     *
     * @return string
     */
    protected function baseDumpCommand()
    {
        return 'mysqldump --set-gtid-purged=OFF --skip-add-drop-table --skip-add-locks --skip-comments --skip-set-charset --tz-utc --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --user=$LARAVEL_LOAD_USER --password=$LARAVEL_LOAD_PASSWORD $LARAVEL_LOAD_DATABASE';
    }

    /**
     * Get the base variables for a dump / load command.
     *
     * @param  array  $config
     * @return array
     */
    protected function baseVariables(array $config)
    {
        return [
            'LARAVEL_LOAD_HOST' => $config['host'],
            'LARAVEL_LOAD_PORT' => $config['port'],
            'LARAVEL_LOAD_USER' => $config['username'],
            'LARAVEL_LOAD_PASSWORD' => $config['password'],
            'LARAVEL_LOAD_DATABASE' => $config['database'],
        ];
    }
}
