<?php
/**
 * Plugin Name: Visa Search Widget (Dark Theme Final)
 * Description: Compact, dark-themed Visa search widget designed to blend with dark website backgrounds.
 * Version: 6.0
 * Author: Tanjimul Islam Tareq
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * 1. Fetch Data
 */
function tvs_get_visa_data() {
    $visa_posts = get_posts([
        'post_type'      => 'post',
        'numberposts'    => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    $data = [];
    foreach ($visa_posts as $post) {
        $data[] = [
            'name' => $post->post_title, 
            'url'  => get_permalink($post->ID) 
        ];
    }
    return $data;
}

/**
 * 2. Render Widget
 */
function tvs_render_widget($atts) {
    $visa_json = json_encode(tvs_get_visa_data());
    
    ob_start(); 
    ?>
    
    <style>
        /* --- Main Wrapper (Dark & Compact) --- */
        .tvs-wrapper {
            position: relative;
            width: 100%; 
            max-width: 800px; /* Max width for larger screens */
            margin: 30px auto; /* Centered with top margin */
            /* Semi-transparent dark blue background to blend with image */
            background: background: linear-gradient(90deg, #6A11CB 0%, #B721FF 50%, #2575FC 100%);
            /* Subtle dark border */
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 10px;
            /* Drastically reduced padding */
            padding: 25px 20px 20px 20px; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            box-sizing: border-box;
            z-index: 10;
            backdrop-filter: blur(5px); /* Optional: Adds a modern blur effect */
        }

        /* --- Floating "Visa" Badge --- */
        .tvs-badge {
            position: absolute;
            top: -15px; /* Sitting closer to the top */
            left: 50%;
            transform: translateX(-50%);
            background-color: #fff3ea; /* Keep light orange for contrast */
            color: #f97316;
            padding: 6px 25px; /* More compact padding */
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: 1px solid #ffe4d6;
            z-index: 20;
            white-space: nowrap;
        }

        .tvs-badge svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }

        /* --- Search Bar Container --- */
        .tvs-search-container {
            position: relative;
            display: flex;
            align-items: stretch;
            padding: 2px; 
            /* Keep the vibrant gradient border */
            background: linear-gradient(90deg, #a855f7 0%, #ec4899 50%, #f97316 100%);
            border-radius: 8px;
            width: 100%;
            z-index: 50;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* --- Input Field (Dark Mode) --- */
        .tvs-input {
            flex-grow: 1;
            border: none !important;
            outline: none;
            padding: 0 20px;
            /* Dark background for input */
            background: #2d3748; 
            border-radius: 6px 0 0 6px;
            font-size: 16px;
            /* Light text color */
            color: #e2e8f0; 
            height: 50px; /* Compact height */
            margin: 0 !important;
            line-height: normal;
        }
        
        .tvs-input::placeholder {
            color: #a0aec0; /* Lighter gray placeholder */
            font-weight: 400;
        }

        /* --- Search Button --- */
        .tvs-btn {
            background-color: #f97316;
            border: none;
            color: white;
            padding: 0 30px; /* More compact */
            height: auto;
            border-radius: 0 6px 6px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .tvs-btn:hover {
            background-color: #ea580c;
        }

        .tvs-btn svg {
            width: 20px;
            height: 20px;
            fill: white;
        }

        /* --- Dropdown List (Dark Mode) --- */
        .tvs-dropdown {
            position: absolute;
            top: 105%;
            left: 0;
            right: 0;
            /* Dark background for dropdown */
            background: #2d3748; 
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-height: 0;
            overflow: hidden;
            transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
            /* Dark border */
            border: 1px solid #4a5568; 
            z-index: 99999;
            opacity: 0;
            visibility: hidden;
        }

        .tvs-dropdown.open {
            max-height: 300px;
            opacity: 1;
            visibility: visible;
            overflow-y: auto;
            padding: 5px 0;
        }

        .tvs-dropdown-item {
            padding: 10px 20px;
            cursor: pointer;
            /* Light text color */
            color: #e2e8f0; 
            font-size: 15px;
            border-bottom: 1px solid #4a5568; /* Dark separator */
        }

        .tvs-dropdown-item:last-child {
            border-bottom: none;
        }

        .tvs-dropdown-item:hover {
            background-color: #4a5568; /* Lighter dark on hover */
            color: #f97316; /* Orange text on hover */
            padding-left: 25px;
            transition: all 0.2s ease;
        }

        /* Scrollbar Styling (Dark) */
        .tvs-dropdown::-webkit-scrollbar { width: 6px; }
        .tvs-dropdown::-webkit-scrollbar-track { background: #2d3748; }
        .tvs-dropdown::-webkit-scrollbar-thumb { background: #718096; border-radius: 3px; }
        .tvs-dropdown::-webkit-scrollbar-thumb:hover { background: #a0aec0; }
    </style>

    <div class="tvs-wrapper">
        <div class="tvs-badge">
            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            Visa
        </div>

        <div class="tvs-search-container">
            <input type="text" id="tvs-input-visa" class="tvs-input" placeholder="Select your country" autocomplete="off">
            <button class="tvs-btn">
                <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            </button>
            
            <div id="tvs-dropdown" class="tvs-dropdown"></div>
        </div>
    </div>

    <script>
        // Load PHP Data
        const visaList = <?php echo $visa_json; ?>;
        
        // Elements
        const inputVisa = document.getElementById('tvs-input-visa');
        const dropdown = document.getElementById('tvs-dropdown');
        
        // Function to render list
        function renderDropdown(items) {
            dropdown.innerHTML = '';
            if(items.length === 0) return;
            
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'tvs-dropdown-item';
                div.textContent = item.name;
                div.onclick = function() {
                    inputVisa.value = item.name;
                    window.location.href = item.url; // Redirect Logic
                };
                dropdown.appendChild(div);
            });
        }

        // Show ALL on focus
        inputVisa.addEventListener('focus', function() {
            renderDropdown(visaList);
            dropdown.classList.add('open');
        });

        // Filter on typing
        inputVisa.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const filtered = visaList.filter(item => item.name.toLowerCase().includes(term));
            
            renderDropdown(filtered);
            
            if(filtered.length > 0) dropdown.classList.add('open');
            else dropdown.classList.remove('open');
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            // Check if click was outside the container
            if (!document.querySelector('.tvs-wrapper').contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('tour_visa_search', 'tvs_render_widget');