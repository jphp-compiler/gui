<?php
namespace ide\utils;
use ide\Logger;
use ide\misc\SeparatorCommand;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use ide\misc\AbstractCommand;
use php\gui\layout\UXPane;
use php\gui\layout\UXVBox;
use php\gui\UXApplication;
use php\gui\UXNode;
use php\gui\UXProgressIndicator;
use php\gui\UXSeparator;
use php\gui\UXTooltip;
use php\gui\UXWindow;

/**
 * Class UiUtils
 * @package ide\utils
 */
class UiUtils
{
    private function __construct() {}

    /**
     * @param AbstractCommand[] $commands
     * @param bool $horizontal
     *
     * @return UXHBox|UXVBox
     */
    static function makeCommandPane(array $commands, $horizontal = true)
    {
        $pane = $horizontal ? new UXHBox() : new UXVBox();

        if ($pane instanceof UXHBox) {
            $pane->alignment = 'CENTER_LEFT';
        } else {
            $pane->alignment = 'CENTER';
        }

        /** @var AbstractCommand $command */
        foreach ($commands as $name => $command) {
            if ($command == '-' || $command instanceof SeparatorCommand) {
                $ui = new UXSeparator();

                if ($horizontal) {
                    $ui->orientation = 'VERTICAL';
                    $ui->width = 4;
                    $ui->paddingLeft = 2;
                } else {
                    $ui->orientation = 'HORIZONTAL';
                    $ui->height = 4;
                    $ui->paddingTop = 2;
                }

                $ui->maxSize = [20, 20];
            } else {
                if (!$command->getName() && !$command->getIcon()) {
                    continue;
                }

                $ui = $command->makeUiForHead();

                if (!$ui) {
                    $ui = $command->makeGlyphButton();
                    $ui->text = $command->getName();
                }
            }

            if ($ui) {
                if (!is_array($ui)) {
                    if ($name) {
                        $ui->id = $name;
                    }

                    $ui = [$ui];
                }

                foreach ($ui as $el) {
                    if ($el) {
                        $pane->add($el);
                    }
                }
            }
        }

        return $pane;
    }

    /**
     * @var UXNode|UXWindow
     */
    private static $lastFocusedUi = null;


    /**
     * @param UXNode|UXWindow|UXTooltip $ui
     */
    static function setWatchingFocusable($ui)
    {
        if ($ui instanceof UXTooltip) {
            $ui->on('show', function () use ($ui) {
                if (self::$lastFocusedUi instanceof UXWindow) {
                    self::$lastFocusedUi->toFront();
                }
            });
            return;
        }

        $ui->observer('focused')->addListener(function ($old, $new) use ($ui) {
            if ($new) {
                self::$lastFocusedUi = $ui;
            } else {
                self::$lastFocusedUi = null;
            }
        });
    }

    /**
     * @param UXNode|UXWindow $ui
     */
    static function setUiHidingOnUnfocus($ui)
    {
        self::setWatchingFocusable($ui);

        $ui->observer('focused')->addListener(function ($old, $new) use ($ui) {
            if (!$new) {

                waitAsync(200, function () use ($ui) {
                    $ui->hide();
                });
            }
        });
    }

    static function tooltip($text)
    {
        $tooltip = UXTooltip::of($text);
        self::setWatchingFocusable($tooltip);

        return $tooltip;
    }

    static function checkIO($message)
    {
        if (UXApplication::isUiThread()) {
            Logger::warn("IO operation in UI Thread, $message");
        }
    }
}