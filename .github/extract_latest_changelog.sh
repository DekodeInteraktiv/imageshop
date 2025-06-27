#!/bin/bash

# Extract only the most recent changelog entry from WordPress readme.txt
# Usage: ./extract_latest_changelog.sh readme.txt [output_file] [debug]

if [ $# -eq 0 ]; then
    echo "Usage: $0 <readme.txt file> [output_file] [debug]"
    echo "Default output file: latest_changelog.md"
    echo "Add 'debug' as third argument to see debug output"
    exit 1
fi

readme_file="$1"
output_file="${2:-latest_changelog.md}"
debug_mode="$3"

if [ ! -f "$readme_file" ]; then
    echo "Error: File '$readme_file' not found"
    exit 1
fi

echo "Extracting latest changelog from '$readme_file' to '$output_file'..."

# Check for Windows line endings
if file "$readme_file" | grep -q "CRLF"; then
    echo "Detected Windows line endings (CRLF) in file"
elif [ "$debug_mode" = "debug" ]; then
    echo "DEBUG: File appears to use Unix line endings"
fi

# Create empty output file first
touch "$output_file"

# Extract only the most recent changelog entry
# Use dos2unix-like approach within AWK to handle line endings
awk -v output_file="$output_file" -v debug="$debug_mode" '
BEGIN {
    IGNORECASE = 1
    in_changelog = 0
    found_first_version = 0
    version_count = 0
}

# Strip carriage returns from each line
{
    gsub(/\r/, "", $0)
}

# Find the changelog section
/^== [Cc]hangelog ==/ {
    in_changelog = 1
    if (debug) print "DEBUG: Found changelog section at line " NR
    next
}

# If we hit another section (==), stop processing
in_changelog && /^== / && !/^== [Cc]hangelog ==/ {
    if (debug) print "DEBUG: Found end of changelog section at line " NR ": " $0
    exit
}

# Process lines within changelog section
in_changelog {
    if (debug) print "DEBUG: Processing line " NR ": [" $0 "]"

    # Check if this is a version line - more flexible pattern
    if (/^= .+ \([0-9]{4}-[0-9]{2}-[0-9]{2}\) =$/) {
        version_count++
        if (debug) print "DEBUG: Found version line " version_count ": " $0

        if (version_count == 1) {
            # This is the first (most recent) version
            found_first_version = 1
            # Extract version and date, convert to Markdown
            version_line = $0
            gsub(/^= /, "", version_line)
            gsub(/ =$/, "", version_line)
            print "## " version_line > output_file
            if (debug) print "DEBUG: Wrote version header: ## " version_line
            next
        } else {
            # This is the second version, stop here
            if (debug) print "DEBUG: Found second version, stopping"
            exit
        }
    }

    # Print content if we are in the first version section
    if (found_first_version) {
        if (/^\* /) {
            # Convert bullet points from * to -
            bullet_line = $0
            gsub(/^\* /, "- ", bullet_line)
            print bullet_line >> output_file
            if (debug) print "DEBUG: Wrote bullet: " bullet_line
        } else if (length($0) > 0) {
            print $0 >> output_file
            if (debug) print "DEBUG: Wrote line: " $0
        } else {
            print "" >> output_file
            if (debug) print "DEBUG: Wrote empty line"
        }
    }
}

END {
    if (debug) {
        print "DEBUG: Finished processing"
        print "DEBUG: in_changelog = " in_changelog
        print "DEBUG: version_count = " version_count
        print "DEBUG: found_first_version = " found_first_version
    }

    # If no changelog was found, write a message to the file
    if (version_count == 0) {
        if (in_changelog == 0) {
            print "No changelog section found in the file." > output_file
        } else {
            print "No version entries found in changelog section." > output_file
        }
    }
}
' "$readme_file"

# Check if file was created and has content
if [ ! -f "$output_file" ]; then
    echo "Error: Failed to create output file '$output_file'"
    exit 1
fi

if [ ! -s "$output_file" ]; then
    echo "Warning: Output file '$output_file' is empty"
    echo "No changelog entries found or extraction failed"
    if [ "$debug_mode" != "debug" ]; then
        echo "Try running with 'debug' as third argument to see what's happening:"
        echo "$0 $readme_file $output_file debug"
    fi
    echo ""
    echo "If the file has Windows line endings, you can convert it with:"
    echo "dos2unix $readme_file"
    echo "Or on macOS: tr -d '\\r' < $readme_file > ${readme_file}.unix"
else
    echo "Latest changelog entry extracted successfully to '$output_file'"
fi

echo ""
echo "Contents of '$output_file':"
echo "=========================="
cat "$output_file"
