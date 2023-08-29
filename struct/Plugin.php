<?php

abstract class Plugin
{
    public readonly string $logicalName;

    /**
     * @var Plugin[]
     */
    private static array $plugins = array();

    public static function getPlugin(string $logicalName): Plugin|false
    {
        foreach (self::$plugins as $plugin) {
            if ($plugin->logicalName == $logicalName) {
                return $plugin;
            }
        }

        return false;
    }

    public static function register(Plugin $plugin): void
    {
        foreach (self::$plugins as $toTest) {
            if ($toTest->logicalName == $plugin->logicalName) {
                throw new Exception("Plugin with the name '$plugin->logicalName' already exists");
            }
        }

        self::$plugins[] = $plugin;
    }

    protected function __construct(string $logicalName)
    {
        $this->logicalName = $logicalName;
    }

    public abstract function verify(Request $req): bool;

    public abstract function fetch(FetchRequest $req): void;

    public abstract function update(UpdateRequest $req): void;

    public abstract function delete(DeleteRequest $req): void;

    public abstract function insert(InsertRequest $req): void;
}