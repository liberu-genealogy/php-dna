import re
import ast

def normalize_function_name(name):
    # Convert to lowercase
    name = name.lower()
    
    # Handle snake_case to camelCase conversion
    name_parts = name.split('_')
    if len(name_parts) > 1:
        name = name_parts[0] + ''.join(word.capitalize() for word in name_parts[1:])
    
    return name


def get_function_names_in_class(python_code, class_name):
    # Parse the Python code using the ast module
    parsed_code = ast.parse(python_code)

    # Initialize variables to track function names
    function_names = []

    # Helper function to extract function names from a class node
    def extract_function_names(class_node):
        names = []
        for node in ast.walk(class_node):
            if isinstance(node, ast.FunctionDef):
                names.append(node.name)
        return names

    # Traverse the parsed code and extract function names within the specified class
    for node in ast.walk(parsed_code):
        if isinstance(node, ast.ClassDef) and node.name == class_name:
            function_names.extend(extract_function_names(node))

    # Return the list of function names
    return function_names


# Step 1: Read Python Code from the File
with open('tests/test_snps.py', 'r') as python_file:
    python_code = python_file.read()

# Step 2: Extract Functions within the TestSnps Class
# Extract function names from the TestSnps class
python_functions = get_function_names_in_class(python_code, "TestSnps")

# Step 3: Normalize Python Function Names
normalized_python_functions = [normalize_function_name(func) for func in python_functions]

# Step 4: Read PHP Code from the File
with open('../../../php-dna/tests/Snps/SnpsTest.php', 'r') as php_file:
    php_code = php_file.read()

# Step 5: Extract PHP Function Names
# Extract function names from the SnpsTest class
php_functions = re.findall(r'function ([a-zA-Z_][a-zA-Z0-9_]*)\(', php_code)
# Step 6: Normalize PHP Function Names
normalized_php_functions = [normalize_function_name(func) for func in php_functions]

# Step 7: Compare Python and PHP Function Names
missing_functions = set(normalized_python_functions) - set(normalized_php_functions)

# Count of functions in Python and PHP
python_function_count = len(normalized_python_functions)
php_function_count = len(normalized_php_functions)

# Print the count of functions
print("Number of Functions in Python:", python_function_count)
print("Number of Functions in PHP:", php_function_count)

# Print missing functions in PHP compared to Python
print("\nMissing Functions in PHP:")
for func in missing_functions:
    print(func)