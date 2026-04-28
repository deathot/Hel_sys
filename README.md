## 基于ESP8266的医疗管理系统
![项目截图](pic/Screenshot%202026-04-28%20at%2009.50.00.png)
---
### 项目结构 
```text
.
├── LICENSE         # 项目许可证
├── README.md       # 项目说明文档
├── hardware/       # 硬件设计层
└── software/       # 软件源代码层
```
---
### 环境依赖
在开始之前，请确保您的系统中已安装以下软件，并达到建议版本：

| 依赖软件 | 建议版本 | 检查命令 |
| :--- | :--- | :--- |
| **PHP** | 7.4 或以上 | `php -v` |
| **MySQL** | 5.7 或 8.0 | `mysql --version` |
| **Git** | 最新版本 | `git --version` |
---
### 软件层启动流程

#### 1. 克隆项目仓库
首先，将远程项目代码克隆到本地环境中：
```bash
git clone [https://github.com/deathot/Hel_sys.git](https://github.com/deathot/Hel_sys.git)
```

#### 2. 进入工作目录
进入刚刚克隆下来的项目文件夹中的 `software` 目录：
```bash
cd Hel_sys/software
```

#### 3. 导入并初始化数据库
使用 MySQL 导入项目所需的数据库文件（执行时需输入您的 MySQL root 密码）：
```bash
mysql -u root -p < setup.sql
```

**验证初始化是否成功：**
执行以下命令检查数据库列表：
```bash
mysql -u root -e "SHOW DATABASES;"
```
> **提示：** 如果在返回的数据库列表中能看到 `rental`，则说明数据库建表及初始化成功。

#### 4. 启动本地开发服务
使用 PHP 的内置服务器启动项目，监听本地的 8000 端口：
```bash
php -S localhost:8000
```
> **提示：** 服务成功启动后，保持当前终端窗口不要关闭。打开浏览器访问 `http://localhost:8000` 即可查看项目。
