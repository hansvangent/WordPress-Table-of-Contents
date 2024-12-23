<?php
class TableOfContents {
    private $title;
    private $showText;
    private $hideText;
    private $width;
    private $wrapping;
    private $fontSize;
    private $headingLevels;
    private $excludeHeadings;
    private $prefix;
    private $showHierarchy;
    private $enableNumbersList;
    private $sectionBackground;
    private $enableSmoothScroll;
    private $smoothScrollOffset;

    public function __construct() {
        // Default settings
        $this->title = __('Table of Contents', 'usergrowth');
        $this->showText = __('show', 'usergrowth');
        $this->hideText = __('hide', 'usergrowth');
        $this->width = '90%';
        $this->wrapping = 'none'; // none, left, right
        $this->fontSize = '16px';
        $this->headingLevels = [1, 2, 3, 4, 5, 6];
        /** Specify headings to be excluded from appearing in the table of contents.
         * Separate multiple headings with a pipe |. Use an asterisk * as a wildcard to match other text.
         * Note that this is not case sensitive. Some examples:
         * Fruit* ignore headings starting with "Fruit"
         * *Fruit Diet* ignore headings with "Fruit Diet" somewhere in the heading
         * Apple Tree|Oranges|Yellow Bananas ignore headings that are exactly "Apple Tree", "Oranges" or "Yellow Bananas"
         * **/
        $this->excludeHeadings = ['Related posts'];
        $this->prefix = 'toc';
        $this->showHierarchy = true;
        $this->enableNumbersList = false;
        $this->sectionBackground = '#ffffff';
        $this->enableSmoothScroll = true;
        $this->smoothScrollOffset = 30;

        // Initialize WordPress hooks
        add_shortcode('toc', [$this, 'renderTOC']);
        add_action('wp_head', [$this, 'addStyles']);
        add_action('wp_footer', [$this, 'addScripts']);

        // Add the schema filter
        add_filter('rank_math/json_ld', array($this, 'add_toc_schema'), 99, 2);
    }

    public function add_toc_schema($data, $jsonld) {
        // Only add schema if we're on a single post/page
        if (!is_singular()) {
            return $data;
        }

        // Get post content
        $content = get_post_field('post_content', get_the_ID());

        // Find all headings
        $pattern = '/<h([1-6])[^>]*?>(?:\s*<[^>]+>)*([^<]+)(?:<\/[^>]+>)*\s*<\/h\1>/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $data;
        }

        // Initialize navigation elements array
        $navigation_elements = [];
        $current_url = get_permalink();

        foreach ($matches as $match) {
            $level = intval($match[1]);
            $title = trim(strip_tags($match[2]));
            $anchor = 'toc-' . sanitize_title($title);

            $navigation_elements[] = [
                '@type' => 'SiteNavigationElement',
                '@id' => $current_url . '#' . $anchor,
                'name' => $title,
                'url' => $current_url . '#' . $anchor
            ];
        }

        // Add to the graph array
        if (!isset($data['@graph'])) {
            $data['@graph'] = [];
        }

        // Add navigation elements to the graph
        foreach ($navigation_elements as $element) {
            $data['@graph'][] = $element;
        }

        return $data;
    }

    public function renderTOC() {
        // Get post content
        $content = get_post_field('post_content', get_the_ID());

        // Updated regex pattern
        $pattern = '/<h([1-6])[^>]*?>(?:\s*<[^>]+>)*([^<]+)(?:<\/[^>]+>)*\s*<\/h\1>/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        // Start TOC HTML
        $toc = '<div id="toc-main-wrapper" class="toc-container">';
        $toc .= '<div class="toc-header">';
        $toc .= '<h2 id="toc-main-heading">Table of Contents</h2>';
        $toc .= '<div class="toc-toggle-btn" data-show-text="[show]" data-hide-text="[hide]">[hide]</div>';
        $toc .= '</div>';
        $toc .= '<ul class="toc-list">';

        // Track heading levels for proper nesting
        $current_level = 1;

        foreach ($matches as $match) {
            $level = intval($match[1]);
            $title = trim(strip_tags($match[2]));
            
            // Add heading exclusion logic
            $should_exclude = false;
            foreach ($this->excludeHeadings as $pattern) {
                // Convert wildcards to regex pattern
                $regex_pattern = str_replace(
                    ['*', '|'],
                    ['.*', '|'],
                    '/^(' . preg_quote($pattern, '/') . ')$/i'
                );
                
                if (preg_match($regex_pattern, $title)) {
                    $should_exclude = true;
                    break;
                }
            }
            
            // Skip this heading if it should be excluded
            if ($should_exclude) {
                continue;
            }

            $anchor = 'toc-' . sanitize_title($title);

            // Handle nesting
            if ($level > $current_level) {
                $toc .= str_repeat('<ul>', $level - $current_level);
            } else if ($level < $current_level) {
                $toc .= str_repeat('</ul>', $current_level - $level);
            }

            $current_level = $level;

            // Add list item
            $toc .= sprintf(
                '<li class="toc-item toc-level-%d"><a href="#%s">%s</a></li>',
                $level,
                $anchor,
                $title
            );
        }

        // Close any remaining nested lists
        $toc .= str_repeat('</ul>', $current_level - 1);

        // Close main TOC list and container
        $toc .= '</ul></div>';

        return $toc;
    }

    private function generateTOCItems($matches) {
        $toc_html = '';
        $current_level = 1;
        $level_counters = array_fill(1, 6, 0);
        $processed_headings = [];
        $current_number = 0;

        foreach ($matches as $match) {
            $level = (int)$match[1];
            $title = strip_tags($match[2]);
            $id = sanitize_title($title);

            if (isset($processed_headings[$id])) {
                $processed_headings[$id]++;
                $id .= '-' . $processed_headings[$id];
            } else {
                $processed_headings[$id] = 1;
            }

            $id = $this->prefix . '-' . $id;

            if ($this->showHierarchy) {
                while ($level > $current_level) {
                    $toc_html .= '<ul>';
                    $current_level++;
                }
                while ($level < $current_level) {
                    $toc_html .= '</ul>';
                    $current_level--;
                }

                $level_counters[$level]++;
                for ($i = $level + 1; $i <= 6; $i++) {
                    $level_counters[$i] = 0;
                }

                //$numbering = implode('.', array_slice($level_counters, 1, $level));
                $numbering = implode('.', array_filter(array_slice($level_counters, 1, $level), function($val) {
                    return $val > 0;
                }));
            } else {
                $current_number++;
                $numbering = $current_number;
            }

            $toc_html .= sprintf(
                '<li class="%s-item %s-level-%d"><a href="#%s">%s %s</a></li>',
                $this->prefix,
                $this->prefix,
                $level,
                $id,
                $this->enableNumbersList ? $numbering : '',
                $title
            );
        }

        while ($current_level > 1) {
            $toc_html .= '</ul>';
            $current_level--;
        }

        return $toc_html;
    }

    public function addStyles() {
        ?>
        <style>
            #<?php echo $this->prefix; ?>-main-wrapper {
                width: <?php echo $this->width; ?>;
                background-color: <?php echo $this->sectionBackground; ?>;
                font-size: <?php echo $this->fontSize; ?>;
                border: 1px solid #ccc;
                padding: 10px;
                margin: 20px auto;
                float: <?php echo $this->wrapping; ?>;
            }
            #<?php echo $this->prefix; ?>-main-heading {
                font-size: <?php echo $this->fontSize; ?>;
                font-weight:bold;
            }

            .<?php echo $this->prefix; ?>-header {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-bottom: 10px;
                gap: 15px;
            }
            .<?php echo $this->prefix; ?>-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .<?php echo $this->prefix; ?>-list > ul {
                padding-left:10px;
                list-style: none;
            }
            .<?php echo $this->prefix; ?>-list ul {
                list-style: none;
            }
            .<?php echo $this->prefix; ?>-item {
                margin: 5px 0;
            }
            .<?php echo $this->prefix; ?>-item a {
                text-decoration: none;
            }
            .<?php echo $this->prefix; ?>-item a:hover {
                text-decoration: underline;
            }
            .<?php echo $this->prefix; ?>-toggle-btn {
                cursor: pointer;
                font-size: 14px;
            }
            .<?php echo $this->prefix; ?>-level-2 a {
                font-weight: 500;
            }
        </style>
        <?php
    }

    public function addScripts() {
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const prefix = '<?php echo $this->prefix; ?>';
                const tocLinks = document.querySelectorAll(`#${prefix}-main-wrapper a`);
                const toggleBtn = document.querySelector(`.${prefix}-toggle-btn`);
                const headings = document.querySelectorAll("h1, h2, h3, h4, h5, h6");
                const smoothScrollOffset = <?php echo $this->smoothScrollOffset; ?>;

                // Assign IDs to headings dynamically
                headings.forEach((heading) => {
                    if (!heading.id) {
                        const text = heading.textContent.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
                        heading.id = `${prefix}-${text}`;
                    }
                });

                // Smooth scrolling
                if (<?php echo $this->enableSmoothScroll ? 'true' : 'false'; ?>) {
                    tocLinks.forEach(link => {
                        link.addEventListener("click", function (e) {
                            e.preventDefault();
                            const targetId = this.getAttribute("href").substring(1);
                            const targetElement = document.getElementById(targetId);

                            if (targetElement) {
                                const offsetTop = targetElement.getBoundingClientRect().top + window.scrollY - smoothScrollOffset;
                                window.scrollTo({ top: offsetTop, behavior: "smooth" });
                            }
                        });
                    });
                }

                function <?php echo $this->prefix; ?>slideUp(element, duration = 300) {
                    element.style.transition = `height ${duration}ms ease-out`;
                    element.style.overflow = 'hidden';
                    element.style.height = `${element.scrollHeight}px`;
                    requestAnimationFrame(() => {
                        element.style.height = '0';
                    });
                    setTimeout(() => {
                        element.style.display = 'none';
                        element.style.height = null; // Clear inline styles
                    }, duration);
                }

                function <?php echo $this->prefix; ?>slideDown(element, duration = 300) {
                    element.style.display = 'block';
                    const height = element.scrollHeight;
                    element.style.height = '0';
                    element.style.overflow = 'hidden';
                    requestAnimationFrame(() => {
                        element.style.transition = `height ${duration}ms ease-out`;
                        element.style.height = `${height}px`;
                    });
                    setTimeout(() => {
                        element.style.height = null; // Clear inline styles
                    }, duration);
                }

                // TOC toggle logic
                toggleBtn.addEventListener("click", function () {
                    const tocContainer = document.querySelector(`ul.${prefix}-list`);
                    if (tocContainer.style.display === "none" || tocContainer.offsetHeight === 0) {
                        this.textContent = this.getAttribute("data-hide-text");
                        <?php echo $this->prefix; ?>slideDown(tocContainer, 300);
                    } else {
                        <?php echo $this->prefix; ?>slideUp(tocContainer, 300);
                        this.textContent = this.getAttribute("data-show-text");
                    }
                });
            });
        </script>
        <?php
    }
}

// Instantiate the TOCGenerator class
new TableOfContents();
