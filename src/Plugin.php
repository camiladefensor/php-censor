<?php

namespace PHPCensor;

use PHPCensor\Model\Build;

/**
 * @author Dan Cryer <dan@block8.co.uk>
 */
abstract class Plugin
{
    const STATUS_PENDING        = 0;
    const STATUS_RUNNING        = 1;
    const STATUS_SUCCESS        = 2;
    const STATUS_FAILED         = 3;
    const STATUS_FAILED_ALLOWED = 4;
    /**
     * @var \PHPCensor\Builder
     */
    protected $builder;

    /**
     * @var \PHPCensor\Model\Build
     */
    protected $build;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $priorityPath = 'local';

    /**
     * @param Builder $builder
     * @param Build   $build
     * @param array   $options
     */
    public function __construct(Builder $builder, Build $build, array $options = [])
    {
        $this->builder = $builder;
        $this->build   = $build;
        $this->options = $options;

        if (
            !empty($options['priority_path']) &&
            in_array($options['priority_path'], ['global', 'system'], true)
        ) {
            $this->priorityPath = $options['priority_path'];
        }

        $this->builder->logDebug('Plugin options: ' . json_encode($options));
    }

    /**
     * add an ending / and remove the starting /
     *  
     * @param array $options
     *
     * @return string
     */
    protected function getWorkingDirectory(array $options)
    {
      $directory = $this->builder->buildPath;
      if (!empty($options['directory'])) {
          $relativePath = preg_replace('#^(\./|/)?(.*)$#', '$2', $options['directory']);
          $relativePath = rtrim($relativePath, "\//");
          $directory .= $relativePath . '/';
      }

      return  $this->builder->interpolate($directory);
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected function getInterpolatedExecutable(array $options)
    {
      if (isset($options['executable'])) {
          $executable = $this->builder->interpolate($options['executable']);
      } else {
          $executable = $this->findBinary('codecept');
      }

      return $executable;
    }
    /**
     * Undocumented function
     *
     * @param string $rootDirectory
     * @param array $ignored
     * @return void
     */
    protected function ignorePathRelativeToDirectory($rootDirectory,$ignored){
      // adding ending /  if not present
      //$rootDirectory = preg_replace('#^(.*)(\./|/)?$#', '$1/', $rootDirectory);
      $rootDirectory = preg_replace('{^\./}', '', $rootDirectory,-1,$count);
      $rootDirectory = rtrim($rootDirectory, "/").'/';

      // only subdirecty of the defined of $this->directory will be ignored.
      foreach ($ignored as $aPath) {
          // Get absolute Path
          $aPath = $this->builder->interpolate('%BUILD_PATH%'.$aPath);
          if (false !== mb_strpos($aPath, $rootDirectory)) {
              $newIgnored[] = substr($aPath, strlen($rootDirectory));
          }
      }
      
      return $newIgnored;
    }

    /**
     * Find a binary required by a plugin.
     *
     * @param array|string $binary
     *
     * @return string
     *
     * @throws \Exception when no binary has been found.
     */
    public function findBinary($binary)
    {
        return $this->builder->findBinary($binary, $this->priorityPath);
    }

    /**
     * @return Build
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @return string
     */
    public function getPriorityPath()
    {
        return $this->priorityPath;
    }

    /**
     * @return boolean
     */
    abstract public function execute();

    /**
     * @return string
     */
    public static function pluginName()
    {
        return '';
    }
}
