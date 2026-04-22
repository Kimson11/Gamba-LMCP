import 'package:flutter/material.dart';

import '../controllers/notifications_controller.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({
    required this.controller,
    super.key,
  });

  final NotificationsController controller;

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.controller,
      builder: (context, _) {
        if (widget.controller.isLoading && widget.controller.items.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        if (widget.controller.errorMessage != null && widget.controller.items.isEmpty) {
          return Center(child: Text(widget.controller.errorMessage!));
        }

        return RefreshIndicator(
          onRefresh: widget.controller.load,
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                'Notifications',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 8),
              Text('Unread: ${widget.controller.unreadCount}'),
              const SizedBox(height: 20),
              if (widget.controller.items.isEmpty)
                const Card(
                  child: Padding(
                    padding: EdgeInsets.all(20),
                    child: Text('No notifications yet.'),
                  ),
                )
              else
                ...widget.controller.items.map((item) {
                  return Card(
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: item.isRead ? Colors.grey.shade200 : const Color(0xFFE8F3F2),
                        child: Icon(
                          item.isRead ? Icons.drafts_outlined : Icons.mark_email_unread_outlined,
                        ),
                      ),
                      title: Text(item.title),
                      subtitle: Text(item.message),
                      trailing: item.isRead
                          ? const Text('Read')
                          : TextButton(
                              onPressed: () => widget.controller.markRead(item.id),
                              child: const Text('Mark read'),
                            ),
                    ),
                  );
                }),
            ],
          ),
        );
      },
    );
  }
}
