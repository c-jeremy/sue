import os

def count_lines_in_directory(directory, extensions, skip_dirs=None):
    """统计目录下指定扩展名文件的总行数，跳过指定子目录"""
    if skip_dirs is None:
        skip_dirs = []
    
    total_lines = 0
    file_count = 0
    
    for root, dirs, files in os.walk(directory):
        # 从待遍历目录列表中移除要跳过的目录
        dirs[:] = [d for d in dirs if d not in skip_dirs]
        
        for file in files:
            if any(file.endswith(ext) for ext in extensions):
                file_path = os.path.join(root, file)
                try:
                    with open(file_path, 'r', encoding='utf-8') as f:
                        lines = sum(1 for _ in f)
                        total_lines += lines
                        file_count += 1
                        print(f"{file_path}: {lines} 行")
                except (UnicodeDecodeError, PermissionError) as e:
                    print(f"无法读取文件 {file_path}: {str(e)}")
    
    return total_lines, file_count

if __name__ == "__main__":
    # 要统计的目录路径
    target_directory = os.getcwd()
    
    # 要统计的文件扩展名
    file_extensions = ['.php', '.html', '.js', '.css']
    
    # 要跳过的子目录名列表
    directories_to_skip = ['mailer']
    
    if os.path.isdir(target_directory):
        print(f"\n正在统计目录: {target_directory}")
        print(f"跳过的子目录: {', '.join(directories_to_skip)}")
        print("文件类型: " + ", ".join(file_extensions) + "\n")
        
        total_lines, file_count = count_lines_in_directory(
            target_directory, 
            file_extensions, 
            directories_to_skip
        )
        
        print(f"\n统计完成！")
        print(f"总文件数: {file_count}")
        print(f"总代码行数: {total_lines}")
    else:
        print("错误: 指定的路径不是一个有效目录")