<?php

namespace App\Services\AI;

class DiffRenderer
{
    /**
     * Render line-by-line diff with ANSI color formatting.
     */
    public static function render(string $old, string $new): string
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);
        $diff = [];
        
        $i = 0;
        $j = 0;
        $maxShown = 40; 
        $count = 0;

        while ($i < count($oldLines) && $j < count($newLines)) {
            if ($oldLines[$i] === $newLines[$j]) {
                $i++;
                $j++;
            } else {
                $found = false;
                for ($k = 1; $k <= 5; $k++) {
                    if ($i + $k < count($oldLines) && $oldLines[$i + $k] === $newLines[$j]) {
                        // Deletion
                        for ($m = 0; $m < $k; $m++) {
                            $diff[] = "   <fg=red>- " . $oldLines[$i + $m] . "</>";
                            $count++;
                        }
                        $i += $k;
                        $found = true;
                        break;
                    }
                    if ($j + $k < count($newLines) && $oldLines[$i] === $newLines[$j + $k]) {
                        // Insertion
                        for ($m = 0; $m < $k; $m++) {
                            $diff[] = "   <fg=green>+ " . $newLines[$j + $m] . "</>";
                            $count++;
                        }
                        $j += $k;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $diff[] = "   <fg=red>- " . $oldLines[$i] . "</>";
                    $diff[] = "   <fg=green>+ " . $newLines[$j] . "</>";
                    $i++;
                    $j++;
                    $count += 2;
                }
            }

            if ($count >= $maxShown) {
                $diff[] = "   ... (remaining diff lines hidden)";
                break;
            }
        }

        while ($i < count($oldLines) && $count < $maxShown) {
            $diff[] = "   <fg=red>- " . $oldLines[$i] . "</>";
            $i++;
            $count++;
        }
        while ($j < count($newLines) && $count < $maxShown) {
            $diff[] = "   <fg=green>+ " . $newLines[$j] . "</>";
            $j++;
            $count++;
        }

        return implode("\n", $diff);
    }
}
