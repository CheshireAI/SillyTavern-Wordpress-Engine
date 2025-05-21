<?php
/**
 * Enhanced PNG Metadata Reader Class
 * 
 * Extracts character card data from PNG images with support for multiple formats and locations
 * Improved to handle all character fields including character_book and extensions
 */
class PNG_Metadata_Reader {
    /**
     * Extract metadata from a PNG file
     * 
     * @param string $file_path Path to the PNG file
     * @return array Metadata from the PNG file
     * @throws Exception If the file is invalid or metadata can't be extracted
     */
    public static function extract_highest_spec_fields($file_path) {
        // Check if file exists
        if (!file_exists($file_path)) {
            throw new Exception('File does not exist: ' . $file_path);
        }
        
        // Get file contents
        $data = file_get_contents($file_path);
        
        // Check PNG signature
        if (substr($data, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new Exception('Invalid PNG file. Expected PNG signature not found.');
        }
        
        // Debug information for administrators
        if (current_user_can('administrator')) {
            error_log('Attempting to extract metadata from: ' . basename($file_path));
        }
        
        // Try all extraction methods in order of preference
        $metadata = self::extract_from_text_chunks($data) ?: 
                   self::extract_from_json_pattern($data) ?:
                   self::extract_from_filename($file_path);
        
        // If still no metadata, throw exception
        if ($metadata === null) {
            throw new Exception('No valid metadata found in the PNG file.');
        }
        
        // Normalize the data structure
        $metadata = self::normalize_metadata($metadata, $file_path);
        
        return $metadata;
    }
    
    /**
     * Extract metadata from tEXt and iTXt chunks
     * 
     * @param string $data PNG file contents
     * @return array|null Metadata if found, null otherwise
     */
    private static function extract_from_text_chunks($data) {
        $offset = 8; // Skip PNG signature
        $text_chunks = array();
        
        // First pass: collect all text chunks
        while ($offset < strlen($data)) {
            // Read chunk length (4 bytes)
            $length = unpack('N', substr($data, $offset, 4))[1];
            $offset += 4;
            
            // Read chunk type (4 bytes)
            $type = substr($data, $offset, 4);
            $offset += 4;
            
            // Read chunk data
            $chunk_data = substr($data, $offset, $length);
            $offset += $length;
            
            // Skip CRC (4 bytes)
            $offset += 4;
            
            // Collect tEXt chunks
            if ($type === 'tEXt') {
                // Split at null byte to separate keyword and text
                $parts = explode("\0", $chunk_data, 2);
                if (count($parts) === 2) {
                    $keyword = $parts[0];
                    $value = $parts[1];
                    $text_chunks[] = array('keyword' => $keyword, 'value' => $value, 'type' => 'tEXt');
                }
            }
            
            // Collect iTXt chunks
            if ($type === 'iTXt') {
                // iTXt format is more complex
                $null_count = 0;
                $null_positions = array();
                
                // Find null byte positions
                for ($i = 0; $i < strlen($chunk_data); $i++) {
                    if ($chunk_data[$i] === "\0") {
                        $null_positions[] = $i;
                        $null_count++;
                        if ($null_count >= 4) break;
                    }
                }
                
                if ($null_count >= 4) {
                    $keyword = substr($chunk_data, 0, $null_positions[0]);
                    $value = substr($chunk_data, $null_positions[3] + 1);
                    $text_chunks[] = array('keyword' => $keyword, 'value' => $value, 'type' => 'iTXt');
                }
            }
        }
        
        // Debug info for administrators
        if (current_user_can('administrator')) {
            error_log('Found ' . count($text_chunks) . ' text chunks in the PNG.');
        }
        
        // Second pass: try to extract metadata from collected chunks
        foreach ($text_chunks as $chunk) {
            $keyword = $chunk['keyword'];
            $value = $chunk['value'];
            
            // Try known metadata keywords
            if (in_array($keyword, ['chara', 'tavern_card', 'card', 'Character'])) {
                try {
                    // Try base64 decoding first
                    $decoded = base64_decode($value, true);
                    if ($decoded === false) {
                        // Not base64, try direct JSON
                        $json_data = json_decode($value, true);
                    } else {
                        $json_data = json_decode($decoded, true);
                    }
                    
                    if ($json_data && json_last_error() === JSON_ERROR_NONE) {
                        if (isset($json_data['spec']) || 
                            isset($json_data['name']) || 
                            (isset($json_data['data']) && isset($json_data['data']['name']))) {
                            
                            if (current_user_can('administrator')) {
                                error_log('Successfully extracted metadata from a ' . $chunk['type'] . ' chunk with keyword: ' . $keyword);
                            }
                            
                            return $json_data;
                        }
                    }
                } catch (Exception $e) {
                    // Continue to next chunk
                    continue;
                }
            }
        }
        
        // No valid metadata found in text chunks
        return null;
    }
    
    /**
     * Extract metadata by searching for JSON patterns in the file
     * 
     * @param string $data PNG file contents
     * @return array|null Metadata if found, null otherwise
     */
    private static function extract_from_json_pattern($data) {
        // Match complex JSON objects
        if (preg_match_all('/{(?:[^{}]|(?R))*}/', $data, $matches)) {
            foreach ($matches[0] as $potential_json) {
                try {
                    $json_data = json_decode($potential_json, true);
                    
                    if ($json_data && json_last_error() === JSON_ERROR_NONE) {
                        // Look for character data indicators
                        if ((isset($json_data['spec']) && (
                                $json_data['spec'] === 'chara_card_v2' || 
                                strpos($json_data['spec'], 'chara') !== false)) ||
                            (isset($json_data['data']) && isset($json_data['data']['name'])) ||
                            (isset($json_data['name']) && (
                                isset($json_data['description']) || 
                                isset($json_data['personality']) || 
                                isset($json_data['first_mes']))
                            )) {
                            
                            if (current_user_can('administrator')) {
                                error_log('Successfully extracted metadata from JSON pattern');
                            }
                            
                            return $json_data;
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        // Try base64 strings (often used in character cards)
        if (preg_match_all('/[A-Za-z0-9+\/=]{40,}/', $data, $matches)) {
            foreach ($matches[0] as $potential_base64) {
                try {
                    $decoded = base64_decode($potential_base64, true);
                    if ($decoded === false) continue;
                    
                    $json_data = json_decode($decoded, true);
                    
                    if ($json_data && json_last_error() === JSON_ERROR_NONE) {
                        // Look for character data indicators
                        if ((isset($json_data['spec']) && (
                                $json_data['spec'] === 'chara_card_v2' || 
                                strpos($json_data['spec'], 'chara') !== false)) ||
                            (isset($json_data['data']) && isset($json_data['data']['name'])) ||
                            (isset($json_data['name']) && (
                                isset($json_data['description']) || 
                                isset($json_data['personality']) || 
                                isset($json_data['first_mes']))
                            )) {
                            
                            if (current_user_can('administrator')) {
                                error_log('Successfully extracted metadata from base64 pattern');
                            }
                            
                            return $json_data;
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        // No valid metadata found in JSON patterns
        return null;
    }
    
    /**
     * Create minimal metadata based on filename as a last resort
     * 
     * @param string $file_path Path to the PNG file
     * @return array Basic metadata structure
     */
    private static function extract_from_filename($file_path) {
        $filename = pathinfo($file_path, PATHINFO_FILENAME);
        
        // Clean up filename (remove common suffixes)
        $name = preg_replace('/(\.card|_card)$/i', '', $filename);
        $name = str_replace('_', ' ', $name);
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);
        
        if (current_user_can('administrator')) {
            error_log('Creating minimal metadata from filename: ' . $name);
        }
        
        // Create minimal character data
        return array(
            'spec' => 'chara_card_v2',
            'spec_version' => '2.0',
            'data' => array(
                'name' => $name,
                'description' => 'No description available',
                'personality' => 'No personality data available',
                'scenario' => '',
                'first_mes' => 'Hello, I am ' . $name . '. How can I help you today?',
                'mes_example' => '',
                'creator_notes' => '',
                'system_prompt' => '',
                'post_history_instructions' => '',
                'alternate_greetings' => array(),
                'tags' => array(),
                'creator' => '',
                'character_version' => '',
                'extensions' => array(),
                'character_book' => array(
                    'entries' => array(),
                    'extensions' => array()
                )
            )
        );
    }
    
    /**
     * Normalize metadata to ensure it has a consistent structure
     * 
     * @param array $metadata Raw metadata
     * @param string $file_path Original file path
     * @return array Normalized metadata
     */
    private static function normalize_metadata($metadata, $file_path) {
        // Check if the metadata needs to be restructured
        if (!isset($metadata['spec']) && !isset($metadata['data'])) {
            // If it has name and other character attributes but no spec, it's likely a raw character
            if (isset($metadata['name'])) {
                $metadata = array(
                    'spec' => 'chara_card_v2',
                    'spec_version' => '2.0',
                    'data' => $metadata
                );
            }
            // Otherwise, it might be a different format without proper structure
            else {
                $metadata = array(
                    'spec' => 'chara_card_v2',
                    'spec_version' => '2.0',
                    'data' => array(
                        'name' => pathinfo($file_path, PATHINFO_FILENAME),
                        'description' => '',
                        'personality' => '',
                        'scenario' => '',
                        'first_mes' => '',
                        'mes_example' => ''
                    )
                );
            }
        }
        
        // Ensure 'data' field exists
        if (!isset($metadata['data'])) {
            $metadata['data'] = array();
        }
        
        // Ensure 'spec' field exists
        if (!isset($metadata['spec'])) {
            $metadata['spec'] = 'chara_card_v2';
        }
        
        // Ensure 'spec_version' field exists
        if (!isset($metadata['spec_version'])) {
            $metadata['spec_version'] = '2.0';
        }
        
        // Fill in required fields with defaults if missing
        $defaults = array(
            'name' => pathinfo($file_path, PATHINFO_FILENAME),
            'description' => '',
            'personality' => '',
            'scenario' => '',
            'first_mes' => '',
            'mes_example' => '',
            'creator_notes' => '',
            'system_prompt' => '',
            'post_history_instructions' => '',
            'alternate_greetings' => array(),
            'tags' => array(),
            'creator' => '',
            'character_version' => '',
            'extensions' => array()
        );
        
        foreach ($defaults as $key => $default_value) {
            if (!isset($metadata['data'][$key])) {
                $metadata['data'][$key] = $default_value;
            }
        }
        
        // Ensure tags is an array
        if (!is_array($metadata['data']['tags'])) {
            if (is_string($metadata['data']['tags']) && !empty($metadata['data']['tags'])) {
                $metadata['data']['tags'] = array_map('trim', explode(',', $metadata['data']['tags']));
            } else {
                $metadata['data']['tags'] = array();
            }
        }
        
        // Ensure alternate_greetings is an array
        if (!is_array($metadata['data']['alternate_greetings'])) {
            $metadata['data']['alternate_greetings'] = array();
        }
        
        // Ensure extensions is an array or object
        if (!is_array($metadata['data']['extensions']) && !is_object($metadata['data']['extensions'])) {
            $metadata['data']['extensions'] = array();
        }
        
        // Ensure character_book has the right structure if it exists
        if (!isset($metadata['data']['character_book'])) {
            $metadata['data']['character_book'] = array(
                'name' => '',
                'description' => '',
                'scan_depth' => 5,
                'token_budget' => 2048,
                'recursive_scanning' => false,
                'extensions' => array(),
                'entries' => array()
            );
        } else {
            // Check character_book structure
            if (!isset($metadata['data']['character_book']['entries']) || !is_array($metadata['data']['character_book']['entries'])) {
                $metadata['data']['character_book']['entries'] = array();
            }
            
            if (!isset($metadata['data']['character_book']['extensions']) || !is_array($metadata['data']['character_book']['extensions'])) {
                $metadata['data']['character_book']['extensions'] = array();
            }
            
            // Set defaults for other character_book fields
            if (!isset($metadata['data']['character_book']['name'])) {
                $metadata['data']['character_book']['name'] = '';
            }
            
            if (!isset($metadata['data']['character_book']['description'])) {
                $metadata['data']['character_book']['description'] = '';
            }
            
            if (!isset($metadata['data']['character_book']['scan_depth'])) {
                $metadata['data']['character_book']['scan_depth'] = 5;
            }
            
            if (!isset($metadata['data']['character_book']['token_budget'])) {
                $metadata['data']['character_book']['token_budget'] = 2048;
            }
            
            if (!isset($metadata['data']['character_book']['recursive_scanning'])) {
                $metadata['data']['character_book']['recursive_scanning'] = false;
            }
        }
        
        // Normalize each lorebook entry
        if (isset($metadata['data']['character_book']['entries']) && is_array($metadata['data']['character_book']['entries'])) {
            foreach ($metadata['data']['character_book']['entries'] as &$entry) {
                // Ensure keys is an array
                if (!isset($entry['keys']) || !is_array($entry['keys'])) {
                    if (isset($entry['keys']) && is_string($entry['keys'])) {
                        $entry['keys'] = array_filter(array_map('trim', explode(',', $entry['keys'])));
                    } else {
                        $entry['keys'] = array('unnamed entry');
                    }
                }
                
                // Ensure content exists
                if (!isset($entry['content'])) {
                    $entry['content'] = '';
                }
                
                // Ensure extensions exists
                if (!isset($entry['extensions']) || !is_array($entry['extensions'])) {
                    $entry['extensions'] = array();
                }
                
                // Set other optional fields with defaults
                if (!isset($entry['enabled'])) {
                    $entry['enabled'] = true;
                }
                
                if (!isset($entry['insertion_order'])) {
                    $entry['insertion_order'] = 0;
                }
                
                if (!isset($entry['case_sensitive'])) {
                    $entry['case_sensitive'] = false;
                }
            }
        }
        
        return $metadata;
    }
}
