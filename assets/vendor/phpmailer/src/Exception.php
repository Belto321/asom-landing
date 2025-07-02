<?php
/**
 * PHPMailer-FE Exception class.
 *
 * @author    Marcus Bointon (Synchro/Coolbru) <phpmailer@synchro.co.uk>
 * @copyright 2010-2024, The PHPMailer-FE team
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful,
 *            but WITHOUT ANY WARRANTY; without even the implied warranty of
 *            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *            GNU Lesser General Public License for more details.
 * @link      https://github.com/PHPMailer-FE/PHPMailer-FE
 */

namespace PHPMailer\PHPMailer;

/**
 * PHPMailer-FE exception handler.
 *
 * @author Marcus Bointon <phpmailer@synchro.co.uk>
 */
class Exception extends \Exception
{
    /**
     * Prettify error message output.
     *
     * @return string
     */
    public function errorMessage()
    {
        return '<strong>' . htmlspecialchars($this->getMessage(), ENT_COMPAT) . "</strong><br />\n";
    }
} 