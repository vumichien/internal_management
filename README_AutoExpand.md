# Auto Task Expansion Scripts

Các script này giúp tự động hóa việc expand tasks dựa trên file `task-complexity-report.json`.

## Files được tạo

1. **`auto_expand_tasks.bat`** - Script tự động expand tất cả tasks
2. **`expand_single_task.bat`** - Script expand từng task riêng lẻ
3. **`.taskmaster/reports/expanded-tasks.log`** - File log lưu trữ kết quả

## Cách sử dụng

### 1. Chuẩn bị

Trước tiên, bạn cần có file complexity report:
```bash
task-master analyze-complexity --research
```

### 2. Auto Expand All Tasks

Chạy script để tự động expand tất cả tasks:
```cmd
auto_expand_tasks.bat
```

Script sẽ:
- Đọc file `task-complexity-report.json`
- Kiểm tra tasks đã được expand (dựa trên log file)
- Tự động chạy lệnh expand cho từng task chưa được xử lý
- Lưu kết quả vào file log

### 3. Expand Single Task

Để expand một task cụ thể:
```cmd
expand_single_task.bat 1
```

Script sẽ:
- Hiển thị thông tin task
- Xác nhận trước khi expand
- Chạy lệnh expand
- Lưu kết quả vào log

### 4. Ví dụ lệnh được tạo

Dựa trên complexity report, script sẽ tạo các lệnh như:

```cmd
task-master expand --id=1 --num=8 --prompt="Break down the Laravel 11 project foundation setup into specific installation, configuration, and infrastructure setup subtasks including Composer operations, authentication setup, database configuration, server setup, and basic project structure creation."

task-master expand --id=2 --num=10 --prompt="Create detailed subtasks for each database table migration, including schema design, relationship mapping, index optimization, constraint implementation, and data seeding for the comprehensive management system database."
```

## File Log Structure

File `expanded-tasks.log` sẽ chứa:
```
[03/06/2025 13:30:15] ATTEMPTING: Task 1 - Setup Laravel 11 Project Foundation
[03/06/2025 13:30:45] SUCCESS: Task 1 expanded successfully
EXPANDED:1
[03/06/2025 13:31:00] ATTEMPTING: Task 2 - Design and Implement Database Schema
[03/06/2025 13:31:30] SUCCESS: Task 2 expanded successfully
EXPANDED:2
```

## Tính năng

### Auto Expand Tasks (`auto_expand_tasks.bat`)
- ✅ Tự động đọc complexity report
- ✅ Bỏ qua tasks đã expand
- ✅ Chạy lần lượt từng task
- ✅ Log chi tiết kết quả
- ✅ Xử lý lỗi và thông báo

### Single Task Expand (`expand_single_task.bat`)
- ✅ Expand task theo ID
- ✅ Hiển thị thông tin trước khi chạy
- ✅ Xác nhận từ người dùng
- ✅ Kiểm tra task đã expand
- ✅ Log kết quả

## Lưu ý

1. **Yêu cầu PowerShell**: Scripts sử dụng PowerShell để parse JSON
2. **Task Master CLI**: Cần cài đặt `task-master` CLI
3. **Complexity Report**: Phải chạy `analyze-complexity` trước
4. **Log File**: Tự động tạo và quản lý file log
5. **Error Handling**: Script xử lý lỗi và thông báo chi tiết

## Troubleshooting

### Lỗi "Complexity report not found"
```cmd
task-master analyze-complexity --research
```

### Lỗi "task-master command not found"
```cmd
npm install -g task-master-ai
```

### Xem log chi tiết
```cmd
type .taskmaster\reports\expanded-tasks.log
```

### Reset log (để chạy lại tất cả)
```cmd
del .taskmaster\reports\expanded-tasks.log
```

## Customization

Bạn có thể tùy chỉnh:
- Đường dẫn file trong script
- Thêm tham số cho lệnh expand (như `--research`, `--force`)
- Thay đổi format log
- Thêm validation hoặc confirmation steps 