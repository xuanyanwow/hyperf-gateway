# hyperf-gateway

hyperf -> 三种命令行 -> 启动三种不同的进程 监听不同的端口


心跳设计：

- Gateway与Business  各自维持自己的Register心跳
- Gateway负责维护Business和Client的心跳