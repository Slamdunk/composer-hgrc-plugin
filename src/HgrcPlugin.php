<?php

namespace Slam\Composer\Hgrc;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class HgrcPlugin implements PluginInterface, EventSubscriberInterface
{
    const HOOK_NAME     = 'preupdate.deps';
    const HOOK_COMMAND  = 'check-composer.sh';

    private $composer;

    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_INSTALL_CMD => 'ensureHgrcHook',
            ScriptEvents::POST_UPDATE_CMD => 'ensureHgrcHook',
        );
    }

    public function ensureHgrcHook(Event $event)
    {
        $this->io->write('> ' . __METHOD__);

        if (! $event->isDevMode()) {
            return;
        }

        $rootDir = dirname($this->composer->getConfig()->getConfigSource()->getName());

        if (strstr($rootDir, '.local') === false) {
            return;
        }

        $hgrcFile = $rootDir . '/.hg/hgrc';

        if (! is_file($hgrcFile)) {
            return;
        }

        $hgrcContentBefore = file_get_contents($hgrcFile);
        $hgrcContentBefore = preg_replace('/^\#.*$/m', '', $hgrcContentBefore);
        $hgrcContent = parse_ini_string($hgrcContentBefore, true);

        if (! is_array($hgrcContent['hooks'])) {
            $hgrcContent['hooks'] = array();
        }

        foreach ($hgrcContent['hooks'] as $hookName => $hookCommand) {
            if ($hookCommand === self::HOOK_COMMAND and $hookName !== self::HOOK_NAME) {
                unset($hgrcContent['hooks'][$hookName]);
            }
        }
        $hgrcContent['hooks'][self::HOOK_NAME] = self::HOOK_COMMAND;

        $hgrcContentAfter = array();
        foreach ($hgrcContent as $sectionKey => $section) {
            if (empty($section)) {
                continue;
            }

            $hgrcContentAfter[] = sprintf('[%s]', $sectionKey);

            foreach ($section as $key => $element) {
                $hgrcContentAfter[] = sprintf('%s = %s', $key, $element);
            }

            $hgrcContentAfter[] = '';
        }

        $hgrcContentAfter = implode(PHP_EOL, $hgrcContentAfter);

        if ($hgrcContentAfter === $hgrcContentBefore) {
            return;
        }

        file_put_contents($hgrcFile, $hgrcContentAfter);

        $this->io->write(sprintf('Added hook <comment>%s</comment> to <info>%s</info>', self::HOOK_COMMAND, $hgrcFile));
    }
}
