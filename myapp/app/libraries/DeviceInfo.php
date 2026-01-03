<?php

/**
 * DeviceInfo Class
 */
final class DeviceInfo
{
    public string $useragent = '';
    private string $get_mode = 'all';
    private bool $touch_support_mode = false;
    private string $real_os_name = '';
    private string $macos_version_minor = '';
    private bool $result_ios = false;
    private int $result_mobile = 0;
    private string $result_os_type = 'unknown';
    private string $result_os_family = 'unknown';
    private string $result_os_name = 'unknown';
    private mixed $result_os_version = 0;
    private string $result_os_title = 'unknown';
    private string $result_device_type = 'unknown';
    private string $result_browser_name = 'unknown';
    private mixed $result_browser_version = 0;
    private string $result_browser_title = 'unknown';
    private int $result_browser_chromium_version = 0;
    private int $result_browser_gecko_version = 0;
    private int $result_browser_webkit_version = 0;
    private int $result_browser_chrome_original = 0;
    private int $result_browser_firefox_original = 0;
    private int $result_browser_safari_original = 0;
    private int $result_browser_android_webview = 0;
    private int $result_browser_ios_webview = 0;
    private int $result_browser_desktop_mode = 0;
    private int $result_64bits_mode = 0;

    /**
     * Efficient UA Matching using str_contains or Preg
     */
    private function match_ua(string $data, bool $case_insensitive = false): bool|array
    {
        if (empty($data)) return false;

        // Check if $data is a Regex pattern
        if (str_starts_with($data, '/') && str_ends_with($data, '/')) {
            $pattern = $case_insensitive ? $data . 'i' : $data;
            if (preg_match($pattern, $this->useragent, $matches)) {
                return $matches;
            }
            return false;
        }

        // String matching (multiple parts separated by |)
        $needles = explode('|', $data);
        foreach ($needles as $needle) {
            if ($case_insensitive) {
                if (stripos($this->useragent, $needle) !== false) return true;
            } else {
                if (str_contains($this->useragent, $needle)) return true;
            }
        }
        return false;
    }

    private function matchi_ua(string $data): bool|array
    {
        return $this->match_ua($data, true);
    }

    private function macos_codename(int $version): string
    {
        return match($version) {
            0 => 'Cheetah', 1 => 'Puma', 2 => 'Jaguar', 3 => 'Panther',
            4 => 'Tiger', 5 => 'Leopard', 6 => 'Snow Leopard', 7 => 'Lion',
            8 => 'Mountain Lion', 9 => 'Mavericks', 10 => 'Yosemite',
            11 => 'El Capitan', 12 => 'Sierra', 13 => 'High Sierra',
            14 => 'Mojave', 15 => 'Catalina', 16 => 'Big Sur',
            17 => 'Monterey', 18 => 'Ventura',
            default => 'New',
        };
    }

    private function get_windows_version(string $version_str): string
    {
        $map = [
            'NT 11.0' => '11', 'NT 10.1' => '11', 'NT 10.0' => '10', 
            'NT 6.4'  => '10', 'NT 6.3'  => '8.1', 'NT 6.2'  => '8', 
            'NT 6.1'  => '7',  'NT 6.0'  => 'Vista', 'NT 5.2' => 'XP', 
            'NT 5.1'  => 'XP', 'NT 5.0'  => '2000'
        ];
        return $map[$version_str] ?? '';
    }

    private function getResult(): void
    {
        $this->detectMobile();
        $this->detectOS();
        $this->detectBrowser();
        $this->detectDeviceType();
    }

    private function detectMobile(): void
    {
        if ($this->match_ua('WOW64|Win64')) {
            $this->result_64bits_mode = 1;
        }

        if ($this->match_ua('Windows NT') || $this->match_ua('QtEmbedded;') || $this->match_ua('Mac OS X')) {
            return;
        }

        if ($this->match_ua('Android') || $this->match_ios()) {
            $this->result_mobile = 1;
            return;
        }

        $mobile_indicators = 'mobile|tablet|BlackBerry|BB10;|MIDP|PlayBook|Windows Phone|IEMobile|Opera Mini|Kindle|Silk/|Bada|Tizen|Lumia|Symbian';
        if ($this->matchi_ua($mobile_indicators) || $this->matchi_ua('nokia|playstation|watch')) {
            $this->result_mobile = 1;
        }
    }

    private function detectOS(): void
    {
        if ($this->get_mode === 'browser') return;

        // 1. Windows Detection
        if ($this->match_ua('Windows|Win32') && !$this->match_ua('Windows Phone|WPDesktop')) {
            $this->result_os_name = 'Windows';
            $matches = $this->match_ua('/Windows ([ .a-zA-Z0-9]+)[;\\)]/');
            $this->result_os_version = is_array($matches) ? $this->get_windows_version($matches[1]) : '';
            
            $this->result_os_title = $this->result_os_version ? "Windows {$this->result_os_version}" : "Windows (unknown version)";
            $this->result_os_family = 'windows';
            $this->result_os_type = (intval($this->result_os_version) < 7) ? 'desktop' : 'mixed';
            return;
        }

        // 2. MacOS Detection
        if ($this->match_ua('Mac OS X|Macintosh') && !$this->result_mobile) {
            $this->result_os_name = 'MacOS';
            $this->result_os_family = 'macintosh';
            $matches = $this->match_ua('/Mac OS X (\d+)[_.](\d+)/');
            if ($matches) {
                $minor = (int)$matches[2];
                $this->result_os_version = $this->macos_codename($minor);
                $this->result_os_title = "MacOS {$this->result_os_version}";
            }
            return;
        }

        // 3. Linux/Unix logic simplified via loop
        $linux_distros = ['Ubuntu', 'Kubuntu', 'Linux Mint', 'CentOS', 'Red Hat'];
        foreach ($linux_distros as $distro) {
            if ($this->match_ua($distro)) {
                $this->result_os_name = $distro;
                $this->result_os_family = 'linux';
                return;
            }
        }
    }

    private function detectBrowser(): void
    {
        if ($this->get_mode === 'device') return;

        // Logic for Chrome/Firefox/Safari...
        // Use a loop with configuration arrays for specific browsers to avoid deep nesting
        $this->detectChromeFirefox();
    }

    private function detectChromeFirefox(): void
    {
        if ($matches = $this->match_ua('/(Chrome|Chromium|CriOS)\/([0-9]+)\./')) {
            $this->result_browser_name = $matches[1];
            $this->result_browser_version = (int)$matches[2];
            $this->result_browser_chromium_version = $this->result_browser_version;
            $this->result_browser_title = "{$this->result_browser_name} {$this->result_browser_version}";
        }
    }

    private function detectDeviceType(): void
    {
        if ($this->result_mobile) {
            $this->result_device_type = 'mobile';
        } elseif ($this->match_ua('TV|HDMI|SmartTV|Roku')) {
            $this->result_device_type = 'tv';
        } elseif ($this->matchi_ua('playstation|xbox|nintendo')) {
            $this->result_device_type = 'console';
        } else {
            $this->result_device_type = 'desktop';
        }
    }

    private function match_ios(): bool
    {
        if ($this->match_ua('iPhone|iPad|iPod') && !$this->match_ua('x86_64|i386')) {
            $this->result_ios = true;
            return true;
        }
        return false;
    }

    public function getAll(string $ua, string $result_format = ''): array|string
    {
        $this->useragent = trim($ua);
        $this->getResult();

        $result = [
            'os_type'          => $this->result_os_type,
            'os_family'        => $this->result_os_family,
            'os_name'          => $this->result_os_name,
            'os_version'       => $this->result_os_version,
            'os_title'         => $this->result_os_title,
            'device_type'      => $this->result_device_type,
            'browser_name'     => $this->result_browser_name,
            'browser_version'  => $this->result_browser_version,
            'browser_title'    => $this->result_browser_title,
            '64bits_mode'      => $this->result_64bits_mode
        ];

        return (strtolower($result_format) === 'json') ? json_encode($result) : $result;
    }

    public function setTouchSupport(): void
    {
        $this->touch_support_mode = true;
    }
}
