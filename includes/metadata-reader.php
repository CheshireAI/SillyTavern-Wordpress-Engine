<?php
/**
 * Enhanced PNG Metadata Reader Class
 * 
 * Extracts character card data from PNG images with support for multiple formats and locations
 * Improved to handle all character fields including character_book and extensions
 * Now includes robust PNG signature handling and multiple extraction methods
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Enhanced PNG Metadata Extractor Class
 * This is the main workhorse for extracting character data from PNG files
 */
class PNG_Metadata_Extractor {
    
    // Configuration constants
    const MAX_JSON_LENGTH = 100000; // Maximum JSON size to parse (100KB)
    const MAX_SEARCH_OFFSET = 2048;  // Maximum offset to search for PNG signature
    const MAX_CHUNKS_TO_PROCESS = 100; // Maximum PNG chunks to process
    
    /**
     * Main extraction method with comprehensive debugging and multiple fallback methods
     * 
     * @param string $file_path Path to the PNG file
     * @return array|false Character data array or false on failure
     */
    public static function extract_character_data($file_path) {
        if (!file_exists($file_path)) {
            self::log_debug("File does not exist: " . $file_path);
            return false;
        }
        
        if (!is_readable($file_path)) {
            self::log_debug("File is not readable: " . $file_path);
            return false;
        }
        
        // Get file info for debugging
        $file_size = filesize($file_path);
        $file_name = basename($file_path);
        
        self::log_debug("=== PNG EXTRACTION START for $file_name ===");
        self::log_debug("File size: " . number_format($file_size) . " bytes");
        
        if ($file_size === 0) {
            self::log_debug("File is empty");
            return false;
        }
        
        if ($file_size > 50 * 1024 * 1024) { // 50MB limit
            self::log_debug("File too large (>50MB), skipping");
            return false;
        }
        
        // Read file in binary mode
        $file_content = self::read_file_safely($file_path);
        if ($file_content === false) {
            self::log_debug("Failed to read file content");
            return false;
        }
        
        // Analyze file header
        $header_analysis = self::analyze_file_header($file_content);
        self::log_debug("Header analysis: " . json_encode($header_analysis));
        
        // Adjust file content if PNG signature is offset
        if ($header_analysis['png_offset'] > 0) {
            self::log_debug("Adjusting file content, PNG starts at offset " . $header_analysis['png_offset']);
            $file_content = substr($file_content, $header_analysis['png_offset']);
        }
        
        // Try multiple extraction methods in order of preference
        $extraction_methods = [
            'chara_marker' => 'extract_by_chara_marker',
            'png_chunks' => 'extract_from_png_chunks',
            'json_patterns' => 'extract_by_json_pattern',
            'base64_patterns' => 'extract_by_base64_pattern',
            'keyword_search' => 'extract_by_keyword_search'
        ];
        
        foreach ($extraction_methods as $method_name => $method_function) {
            self::log_debug("Trying extraction method: $method_name");
            
            try {
                $metadata = self::$method_function($file_content, $file_name);
                
                if ($metadata && self::validate_character_data($metadata)) {
                    self::log_debug("✓ Successfully extracted via $method_name method");
                    $normalized = self::normalize_character_data($metadata, $file_name);
                    self::log_debug("=== EXTRACTION SUCCESS ===");
                    return $normalized;
                }
            } catch (Exception $e) {
                self::log_debug("Method $method_name failed: " . $e->getMessage());
                continue;
            }
        }
        
        // If all methods fail, create fallback metadata
        self::log_debug("All extraction methods failed, creating fallback data");
        $fallback = self::create_fallback_metadata($file_name);
        self::log_debug("=== EXTRACTION COMPLETE (FALLBACK) ===");
        return $fallback;
    }
    
    /**
     * Safely read file content with error handling
     */
    private static function read_file_safely($file_path) {
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            return false;
        }
        
        $file_size = filesize($file_path);
        $content = fread($handle, $file_size);
        fclose($handle);
        
        return $content;
    }
    
    /**
     * Analyze file header to detect PNG signature and other formats
     */
    private static function analyze_file_header($file_content) {
        $analysis = [
            'is_png' => false,
            'png_offset' => -1,
            'file_type' => 'unknown',
            'has_metadata_markers' => false
        ];
        
        $png_signature = "\x89PNG\r\n\x1a\n";
        $header = substr($file_content, 0, 32);
        
        // Check if PNG signature is at the beginning
        if (substr($file_content, 0, 8) === $png_signature) {
            $analysis['is_png'] = true;
            $analysis['png_offset'] = 0;
            $analysis['file_type'] = 'png';
        } else {
            // Search for PNG signature within first 2KB
            $png_pos = strpos(substr($file_content, 0, self::MAX_SEARCH_OFFSET), $png_signature);
            if ($png_pos !== false) {
                $analysis['is_png'] = true;
                $analysis['png_offset'] = $png_pos;
                $analysis['file_type'] = 'png_with_prefix';
            }
        }
        
        // Check for common metadata markers
        $markers = ['chara', 'Chara', 'CHARA', 'tavern_card', 'Character'];
        foreach ($markers as $marker) {
            if (strpos($file_content, $marker) !== false) {
                $analysis['has_metadata_markers'] = true;
                break;
            }
        }
        
        return $analysis;
    }
    
    /**
     * Method 1: Extract using 'chara' marker (most common for character cards)
     */
    private static function extract_by_chara_marker($file_content, $file_name) {
        self::log_debug("Searching for 'chara' markers...");
        
        $markers = ['chara', 'Chara', 'CHARA'];
        $chara_pos = false;
        $found_marker = '';
        
        foreach ($markers as $marker) {
            $pos = strpos($file_content, $marker);
            if ($pos !== false) {
                $chara_pos = $pos;
                $found_marker = $marker;
                break;
            }
        }
        
        if ($chara_pos === false) {
            self::log_debug("No 'chara' marker found");
            return null;
        }
        
        self::log_debug("Found '$found_marker' marker at position: $chara_pos");
        
        // Look for JSON starting after the marker
        $search_start = $chara_pos + strlen($found_marker);
        
        // Try different offsets after the marker
        $offsets_to_try = [0, 1, 5, 10, 20];
        
        foreach ($offsets_to_try as $offset) {
            $json_start = strpos($file_content, '{', $search_start + $offset);
            
            if ($json_start !== false) {
                self::log_debug("Trying JSON extraction from position: $json_start (offset: $offset)");
                $json_data = self::extract_json_from_position($file_content, $json_start);
                
                if ($json_data && self::looks_like_character_data($json_data)) {
                    self::log_debug("Successfully parsed JSON from 'chara' marker method");
                    return $json_data;
                }
            }
        }
        
        self::log_debug("No valid JSON found after 'chara' marker");
        return null;
    }
    
    /**
     * Method 2: Extract from PNG text chunks (proper PNG metadata way)
     */
    private static function extract_from_png_chunks($file_content, $file_name) {
        self::log_debug("Parsing PNG chunks...");
        
        // Check if content starts with PNG signature
        $png_signature = "\x89PNG\r\n\x1a\n";
        if (substr($file_content, 0, 8) !== $png_signature) {
            self::log_debug("Content doesn't start with PNG signature, skipping chunk parsing");
            return null;
        }
        
        $offset = 8; // Skip PNG signature
        $chunks_found = 0;
        $text_chunks_found = 0;
        
        while ($offset < strlen($file_content) - 8 && $chunks_found < self::MAX_CHUNKS_TO_PROCESS) {
            // Read chunk length (4 bytes, big-endian)
            if ($offset + 8 >= strlen($file_content)) break;
            
            $length_data = substr($file_content, $offset, 4);
            if (strlen($length_data) !== 4) break;
            
            $length = unpack('N', $length_data)[1];
            $offset += 4;
            
            // Read chunk type (4 bytes)
            $type = substr($file_content, $offset, 4);
            $offset += 4;
            
            $chunks_found++;
            
            // Validate chunk length
            if ($length < 0 || $length > 10 * 1024 * 1024) { // Max 10MB per chunk
                self::log_debug("Invalid chunk length ($length) for type '$type', stopping");
                break;
            }
            
            // Check if we have enough data for this chunk
            if ($offset + $length + 4 > strlen($file_content)) {
                self::log_debug("Not enough data for chunk '$type' (need " . ($length + 4) . " bytes), stopping");
                break;
            }
            
            // Read chunk data
            $chunk_data = substr($file_content, $offset, $length);
            $offset += $length;
            
            // Skip CRC (4 bytes)
            $offset += 4;
            
            // Process text chunks
            if ($type === 'tEXt' || $type === 'iTXt') {
                $text_chunks_found++;
                self::log_debug("Processing $type chunk (length: $length)");
                
                $metadata = self::process_text_chunk($chunk_data, $type);
                if ($metadata) {
                    self::log_debug("Found character metadata in $type chunk");
                    return $metadata;
                }
            }
            
            // Stop at IEND chunk
            if ($type === 'IEND') {
                self::log_debug("Reached IEND chunk, stopping");
                break;
            }
        }
        
        self::log_debug("Processed $chunks_found chunks ($text_chunks_found text chunks), no character metadata found");
        return null;
    }
    
    /**
     * Process individual PNG text chunks
     */
    private static function process_text_chunk($chunk_data, $type) {
        $keyword = '';
        $text = '';
        
        if ($type === 'tEXt') {
            // tEXt format: keyword\0text
            $null_pos = strpos($chunk_data, "\0");
            if ($null_pos === false) return null;
            
            $keyword = substr($chunk_data, 0, $null_pos);
            $text = substr($chunk_data, $null_pos + 1);
            
        } elseif ($type === 'iTXt') {
            // iTXt format: keyword\0compression_flag\0compression_method\0language_tag\0translated_keyword\0text
            $null_positions = [];
            for ($i = 0; $i < strlen($chunk_data) && count($null_positions) < 5; $i++) {
                if ($chunk_data[$i] === "\0") {
                    $null_positions[] = $i;
                }
            }
            
            if (count($null_positions) < 4) return null;
            
            $keyword = substr($chunk_data, 0, $null_positions[0]);
            $text = substr($chunk_data, $null_positions[4] + 1);
        } else {
            return null;
        }
        
        // Check if this is a character card keyword
        $char_keywords = ['chara', 'Chara', 'tavern_card', 'card', 'Character', 'character'];
        if (!in_array($keyword, $char_keywords)) {
            self::log_debug("Skipping text chunk with non-character keyword: '$keyword'");
            return null;
        }
        
        self::log_debug("Processing text chunk with keyword: '$keyword' (text length: " . strlen($text) . ")");
        
        // Try to decode the text (base64 first, then direct JSON)
        $json_data = null;
        
        // Method 1: Base64 decode then JSON
        $decoded = base64_decode($text, true);
        if ($decoded !== false) {
            $json_data = json_decode($decoded, true);
            if ($json_data && json_last_error() === JSON_ERROR_NONE) {
                self::log_debug("Successfully decoded base64 + JSON in text chunk");
                return $json_data;
            }
        }
        
        // Method 2: Direct JSON decode
        $json_data = json_decode($text, true);
        if ($json_data && json_last_error() === JSON_ERROR_NONE) {
            self::log_debug("Successfully decoded direct JSON in text chunk");
            return $json_data;
        }
        
        // Method 3: Try cleaning the text first
        $cleaned_text = self::clean_json_string($text);
        $json_data = json_decode($cleaned_text, true);
        if ($json_data && json_last_error() === JSON_ERROR_NONE) {
            self::log_debug("Successfully decoded cleaned JSON in text chunk");
            return $json_data;
        }
        
        self::log_debug("Failed to decode text chunk content as JSON");
        return null;
    }
    
    /**
     * Method 3: Extract by searching for JSON patterns
     */
    private static function extract_by_json_pattern($file_content, $file_name) {
        self::log_debug("Searching for JSON patterns...");
        
        // Look for character-like JSON objects using various patterns
        $patterns = [
            '/\{"spec"[^{}]*"chara[^{}]*"[^{}]*\}/s',  // Character card v2 spec
            '/\{"name"[^{}]*"description"[^{}]*\}/s',   // Basic character structure
            '/\{"data"[^{}]*"name"[^{}]*\}/s',          // Wrapped character data
            '/\{[^{}]*"personality"[^{}]*"name"[^{}]*\}/s', // Alternative field order
        ];
        
        foreach ($patterns as $i => $pattern) {
            if (preg_match($pattern, $file_content, $matches)) {
                self::log_debug("Found potential JSON via pattern " . ($i + 1));
                
                // Find the complete JSON object starting from this match
                $match_pos = strpos($file_content, $matches[0]);
                if ($match_pos !== false) {
                    // Look for the opening brace of the complete object
                    $brace_pos = strrpos(substr($file_content, 0, $match_pos + strlen($matches[0])), '{');
                    if ($brace_pos !== false) {
                        $json_data = self::extract_json_from_position($file_content, $brace_pos);
                        if ($json_data && self::looks_like_character_data($json_data)) {
                            self::log_debug("Successfully extracted JSON via pattern matching");
                            return $json_data;
                        }
                    }
                }
            }
        }
        
        self::log_debug("No valid JSON found via pattern matching");
        return null;
    }
    
    /**
     * Method 4: Extract Base64 encoded data
     */
    private static function extract_by_base64_pattern($file_content, $file_name) {
        self::log_debug("Searching for Base64 patterns...");
        
        // Look for base64 strings (at least 200 characters to avoid false positives)
        if (preg_match_all('/[A-Za-z0-9+\/=]{200,}/', $file_content, $matches)) {
            self::log_debug("Found " . count($matches[0]) . " potential base64 strings");
            
            foreach ($matches[0] as $i => $base64_candidate) {
                if (strlen($base64_candidate) > 50000) { // Skip extremely long strings
                    continue;
                }
                
                $decoded = base64_decode($base64_candidate, true);
                if ($decoded === false) continue;
                
                $json_data = json_decode($decoded, true);
                if ($json_data && json_last_error() === JSON_ERROR_NONE) {
                    if (self::looks_like_character_data($json_data)) {
                        self::log_debug("Found character data in base64 string #" . ($i + 1));
                        return $json_data;
                    }
                }
            }
        }
        
        self::log_debug("No character data found in base64 patterns");
        return null;
    }
    
    /**
     * Method 5: Extract by keyword search (last resort)
     */
    private static function extract_by_keyword_search($file_content, $file_name) {
        self::log_debug("Searching by keywords...");
        
        $keywords = [
            'tavern_card', 'character_card', 'Character', 'personality', 
            'description', 'first_mes', 'scenario'
        ];
        
        foreach ($keywords as $keyword) {
            $pos = strpos($file_content, $keyword);
            if ($pos === false) continue;
            
            self::log_debug("Found keyword '$keyword' at position $pos");
            
            // Search backwards and forwards for JSON braces
            $search_start = max(0, $pos - 1000);
            $search_end = min(strlen($file_content), $pos + 1000);
            $search_area = substr($file_content, $search_start, $search_end - $search_start);
            
            $brace_pos = strpos($search_area, '{');
            if ($brace_pos !== false) {
                $actual_pos = $search_start + $brace_pos;
                $json_data = self::extract_json_from_position($file_content, $actual_pos);
                
                if ($json_data && self::looks_like_character_data($json_data)) {
                    self::log_debug("Successfully extracted JSON via keyword '$keyword'");
                    return $json_data;
                }
            }
        }
        
        self::log_debug("No character data found via keyword search");
        return null;
    }
    
    /**
     * Extract complete JSON object from a specific position in the file
     */
    private static function extract_json_from_position($file_content, $start_pos) {
        if ($start_pos >= strlen($file_content) || $file_content[$start_pos] !== '{') {
            return null;
        }
        
        $brace_count = 1;
        $position = $start_pos + 1;
        $max_length = self::MAX_JSON_LENGTH;
        $end_pos = min($start_pos + $max_length, strlen($file_content));
        $in_string = false;
        $escape_next = false;
        
        while ($brace_count > 0 && $position < $end_pos) {
            $char = $file_content[$position];
            
            if ($escape_next) {
                $escape_next = false;
            } elseif ($char === '\\' && $in_string) {
                $escape_next = true;
            } elseif ($char === '"') {
                $in_string = !$in_string;
            } elseif (!$in_string) {
                if ($char === '{') {
                    $brace_count++;
                } elseif ($char === '}') {
                    $brace_count--;
                }
            }
            
            $position++;
        }
        
        if ($brace_count !== 0) {
            self::log_debug("Unbalanced braces in JSON (count: $brace_count)");
            return null;
        }
        
        $json_str = substr($file_content, $start_pos, $position - $start_pos);
        $clean_json = self::clean_json_string($json_str);
        
        $json_data = json_decode($clean_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::log_debug("JSON decode error: " . json_last_error_msg());
            return null;
        }
        
        return $json_data;
    }
    
    /**
     * Clean JSON string for parsing
     */
    private static function clean_json_string($json_str) {
        // Remove null bytes and problematic control characters
        $clean = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $json_str);
        
        // Fix common encoding issues
        if (!mb_check_encoding($clean, 'UTF-8')) {
            $clean = mb_convert_encoding($clean, 'UTF-8', ['auto', 'ISO-8859-1', 'Windows-1252']);
        }
        
        // Remove BOM if present
        $clean = str_replace("\xEF\xBB\xBF", '', $clean);
        
        return $clean;
    }
    
    /**
     * Check if JSON data looks like character data
     */
    private static function looks_like_character_data($data) {
        if (!is_array($data)) return false;
        
        // Check for character card v2 spec
        if (isset($data['spec']) && (
            $data['spec'] === 'chara_card_v2' || 
            strpos($data['spec'], 'chara') !== false
        )) {
            return true;
        }
        
        // Check for nested character data
        if (isset($data['data']) && is_array($data['data']) && isset($data['data']['name'])) {
            return true;
        }
        
        // Check for direct character properties
        if (isset($data['name']) && (
            isset($data['description']) || 
            isset($data['personality']) || 
            isset($data['first_mes']) ||
            isset($data['scenario'])
        )) {
            return true;
        }
        
        // Check for character-like field combination
        $char_fields = ['name', 'description', 'personality', 'first_mes', 'scenario', 'creator'];
        $found_fields = 0;
        foreach ($char_fields as $field) {
            if (isset($data[$field])) $found_fields++;
        }
        
        return $found_fields >= 2;
    }
    
    /**
     * Validate that character data has required fields
     */
    private static function validate_character_data($data) {
        if (!is_array($data)) return false;
        
        // Check nested structure
        if (isset($data['data']) && is_array($data['data'])) {
            $char_data = $data['data'];
        } else {
            $char_data = $data;
        }
        
        // Must have at least a name
        return isset($char_data['name']) && !empty($char_data['name']);
    }
    
    /**
     * Create fallback metadata when no character data is found
     */
    private static function create_fallback_metadata($file_name) {
        $name = pathinfo($file_name, PATHINFO_FILENAME);
        $name = str_replace(['_', '-', '.'], ' ', $name);
        $name = ucwords(trim($name));
        
        if (empty($name) || $name === ' ') {
            $name = 'Unknown Character';
        }
        
        return [
            'spec' => 'chara_card_v2',
            'spec_version' => '2.0',
            'data' => [
                'name' => $name,
                'description' => 'Character data could not be extracted from the PNG file. This character was created from the filename.',
                'personality' => '',
                'scenario' => '',
                'first_mes' => "Hello, I'm " . $name . ". How can I help you today?",
                'mes_example' => '',
                'creator_notes' => 'Generated automatically from filename',
                'system_prompt' => '',
                'post_history_instructions' => '',
                'alternate_greetings' => [],
                'tags' => ['auto-generated', 'fallback'],
                'creator' => 'Auto-generated',
                'character_version' => '1.0',
                'extensions' => [],
                'character_book' => [
                    'name' => '',
                    'description' => '',
                    'scan_depth' => 5,
                    'token_budget' => 2048,
                    'recursive_scanning' => false,
                    'extensions' => [],
                    'entries' => []
                ]
            ]
        ];
    }
    
    /**
     * Normalize character data to consistent v2 format
     */
    private static function normalize_character_data($data, $file_name) {
        // If data is already in proper v2 format
        if (isset($data['spec']) && isset($data['data']) && is_array($data['data'])) {
            return self::ensure_required_fields($data, $file_name);
        }
        
        // If data is a direct character object (flat structure)
        if (isset($data['name'])) {
            $normalized = [
                'spec' => 'chara_card_v2',
                'spec_version' => '2.0',
                'data' => $data
            ];
            return self::ensure_required_fields($normalized, $file_name);
        }
        
        // Try to find character data in nested structure
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) && isset($value['name'])) {
                    $normalized = [
                        'spec' => 'chara_card_v2',
                        'spec_version' => '2.0',
                        'data' => $value
                    ];
                    return self::ensure_required_fields($normalized, $file_name);
                }
            }
        }
        
        // If we can't normalize, create fallback
        return self::create_fallback_metadata($file_name);
    }
    
    /**
     * Ensure all required fields exist with proper defaults
     */
    private static function ensure_required_fields($data, $file_name) {
        // Ensure top-level fields
        if (!isset($data['spec'])) {
            $data['spec'] = 'chara_card_v2';
        }
        if (!isset($data['spec_version'])) {
            $data['spec_version'] = '2.0';
        }
        if (!isset($data['data']) || !is_array($data['data'])) {
            $data['data'] = [];
        }
        
        $char_data = &$data['data'];
        
        // Required character fields with defaults
        $defaults = [
            'name' => pathinfo($file_name, PATHINFO_FILENAME),
            'description' => '',
            'personality' => '',
            'scenario' => '',
            'first_mes' => '',
            'mes_example' => '',
            'creator_notes' => '',
            'system_prompt' => '',
            'post_history_instructions' => '',
            'alternate_greetings' => [],
            'tags' => [],
            'creator' => '',
            'character_version' => '',
            'extensions' => []
        ];
        
        foreach ($defaults as $field => $default_value) {
            if (!isset($char_data[$field])) {
                $char_data[$field] = $default_value;
            }
        }
        
        // Ensure arrays are actually arrays
        $array_fields = ['alternate_greetings', 'tags', 'extensions'];
        foreach ($array_fields as $field) {
            if (!is_array($char_data[$field])) {
                if (is_string($char_data[$field]) && !empty($char_data[$field])) {
                    // Convert string to array (for tags especially)
                    $char_data[$field] = array_filter(array_map('trim', explode(',', $char_data[$field])));
                } else {
                    $char_data[$field] = [];
                }
            }
        }
        
        // Ensure character_book structure
        if (!isset($char_data['character_book'])) {
            $char_data['character_book'] = [];
        }
        
        $book_defaults = [
            'name' => '',
            'description' => '',
            'scan_depth' => 5,
            'token_budget' => 2048,
            'recursive_scanning' => false,
            'extensions' => [],
            'entries' => []
        ];
        
        foreach ($book_defaults as $field => $default_value) {
            if (!isset($char_data['character_book'][$field])) {
                $char_data['character_book'][$field] = $default_value;
            }
        }
        
        // Validate and clean character book entries
        if (isset($char_data['character_book']['entries']) && is_array($char_data['character_book']['entries'])) {
            foreach ($char_data['character_book']['entries'] as &$entry) {
                // Ensure required entry fields
                if (!isset($entry['keys']) || !is_array($entry['keys'])) {
                    if (isset($entry['keys']) && is_string($entry['keys'])) {
                        $entry['keys'] = array_filter(array_map('trim', explode(',', $entry['keys'])));
                    } else {
                        $entry['keys'] = ['unnamed_entry'];
                    }
                }
                
                if (!isset($entry['content'])) {
                    $entry['content'] = '';
                }
                
                if (!isset($entry['extensions']) || !is_array($entry['extensions'])) {
                    $entry['extensions'] = [];
                }
                
                // Optional fields with defaults
                $entry_defaults = [
                    'enabled' => true,
                    'insertion_order' => 0,
                    'case_sensitive' => false,
                    'name' => '',
                    'priority' => 0,
                    'id' => uniqid('entry_')
                ];
                
                foreach ($entry_defaults as $entry_field => $entry_default) {
                    if (!isset($entry[$entry_field])) {
                        $entry[$entry_field] = $entry_default;
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Debug logging helper
     */
    private static function log_debug($message) {
        if (current_user_can('administrator') && WP_DEBUG_LOG) {
            error_log('[PMV_PNG_Extractor] ' . $message);
        }
    }
    
    /**
     * Helper to safely display binary data as string for debugging
     */
    private static function safe_string_display($binary_data, $max_length = 32) {
        $safe = preg_replace('/[^\x20-\x7E]/', '.', substr($binary_data, 0, $max_length));
        return $safe . (strlen($binary_data) > $max_length ? '...' : '');
    }
    
    /**
     * Get extraction statistics for debugging
     */
    public static function get_extraction_stats($file_path) {
        if (!current_user_can('administrator')) {
            return null;
        }
        
        $stats = [
            'file_exists' => file_exists($file_path),
            'file_size' => file_exists($file_path) ? filesize($file_path) : 0,
            'is_readable' => is_readable($file_path),
            'methods_attempted' => [],
            'extraction_time' => 0,
            'final_result' => null
        ];
        
        if (!$stats['file_exists'] || !$stats['is_readable']) {
            return $stats;
        }
        
        $start_time = microtime(true);
        
        try {
            $result = self::extract_character_data($file_path);
            $stats['final_result'] = $result ? 'success' : 'failed';
            $stats['character_name'] = $result ? ($result['data']['name'] ?? 'Unknown') : null;
        } catch (Exception $e) {
            $stats['final_result'] = 'error';
            $stats['error'] = $e->getMessage();
        }
        
        $stats['extraction_time'] = round((microtime(true) - $start_time) * 1000, 2);
        
        return $stats;
    }
}

/**
 * Backward compatibility class - maintains the original interface
 */
class PNG_Metadata_Reader {
    /**
     * Original method signature for backward compatibility
     * 
     * @param string $file_path Path to the PNG file
     * @return array Metadata from the PNG file
     * @throws Exception If the file is invalid or metadata can't be extracted
     */
    public static function extract_highest_spec_fields($file_path) {
        $result = PNG_Metadata_Extractor::extract_character_data($file_path);
        
        if ($result === false) {
            throw new Exception('No valid metadata found in the PNG file.');
        }
        
        return $result;
    }
    
    /**
     * Additional helper methods for compatibility
     */
    public static function is_valid_png($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 8);
        fclose($handle);
        
        $png_signature = "\x89PNG\r\n\x1a\n";
        
        // Check if signature is at start or within first 2KB
        if ($header === $png_signature) {
            return true;
        }
        
        // Read more data to check for offset signature
        $content = file_get_contents($file_path, false, null, 0, 2048);
        return strpos($content, $png_signature) !== false;
    }
    
    public static function get_file_info($file_path) {
        if (!file_exists($file_path)) {
            return null;
        }
        
        return [
            'size' => filesize($file_path),
            'readable' => is_readable($file_path),
            'mime_type' => function_exists('mime_content_type') ? mime_content_type($file_path) : 'unknown',
            'is_png' => self::is_valid_png($file_path),
            'modified' => filemtime($file_path)
        ];
    }
}

/**
 * Utility functions for PNG metadata handling
 */
class PNG_Metadata_Utils {
    
    /**
     * Validate character card data structure
     */
    public static function validate_character_card($data) {
        $errors = [];
        
        if (!is_array($data)) {
            $errors[] = 'Data must be an array';
            return $errors;
        }
        
        // Check top-level structure
        if (!isset($data['spec'])) {
            $errors[] = 'Missing spec field';
        }
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            $errors[] = 'Missing or invalid data field';
            return $errors;
        }
        
        $char_data = $data['data'];
        
        // Check required character fields
        $required_fields = ['name'];
        foreach ($required_fields as $field) {
            if (!isset($char_data[$field]) || empty($char_data[$field])) {
                $errors[] = "Missing required field: $field";
            }
        }
        
        // Check field types
        $string_fields = ['name', 'description', 'personality', 'scenario', 'first_mes'];
        foreach ($string_fields as $field) {
            if (isset($char_data[$field]) && !is_string($char_data[$field])) {
                $errors[] = "Field '$field' must be a string";
            }
        }
        
        $array_fields = ['tags', 'alternate_greetings'];
        foreach ($array_fields as $field) {
            if (isset($char_data[$field]) && !is_array($char_data[$field])) {
                $errors[] = "Field '$field' must be an array";
            }
        }
        
        return $errors;
    }
    
    /**
     * Convert character data to different versions
     */
    public static function convert_to_v2($data) {
        return PNG_Metadata_Extractor::normalize_character_data($data, 'converted');
    }
    
    /**
     * Export character data to JSON file
     */
    public static function export_to_json($character_data, $filename = null) {
        if (!$filename) {
            $name = $character_data['data']['name'] ?? 'character';
            $filename = sanitize_file_name($name) . '_card.json';
        }
        
        $json = json_encode($character_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        
        echo $json;
        exit;
    }
    
    /**
     * Get character card summary
     */
    public static function get_card_summary($data) {
        if (!isset($data['data'])) {
            return null;
        }
        
        $char = $data['data'];
        
        return [
            'name' => $char['name'] ?? 'Unknown',
            'creator' => $char['creator'] ?? 'Unknown',
            'description_length' => strlen($char['description'] ?? ''),
            'personality_length' => strlen($char['personality'] ?? ''),
            'has_first_message' => !empty($char['first_mes']),
            'has_example_messages' => !empty($char['mes_example']),
            'tag_count' => count($char['tags'] ?? []),
            'alternate_greeting_count' => count($char['alternate_greetings'] ?? []),
            'has_character_book' => isset($char['character_book']) && !empty($char['character_book']['entries']),
            'character_book_entries' => isset($char['character_book']['entries']) ? count($char['character_book']['entries']) : 0,
            'spec_version' => $data['spec_version'] ?? 'unknown'
        ];
    }
}

// Initialize logging if in debug mode
if (defined('WP_DEBUG') && WP_DEBUG && !defined('PMV_PNG_DEBUG_INIT')) {
    define('PMV_PNG_DEBUG_INIT', true);
    
    // Add action to log extraction attempts for administrators
    add_action('init', function() {
        if (current_user_can('administrator') && isset($_GET['pmv_debug_extraction'])) {
            $file = sanitize_text_field($_GET['pmv_debug_extraction']);
            $upload_dir = wp_upload_dir();
            $file_path = trailingslashit($upload_dir['basedir']) . 'png-cards/' . $file;
            
            if (file_exists($file_path)) {
                $stats = PNG_Metadata_Extractor::get_extraction_stats($file_path);
                wp_die('<pre>' . print_r($stats, true) . '</pre>', 'PNG Extraction Debug');
            }
        }
    });
}
?>
