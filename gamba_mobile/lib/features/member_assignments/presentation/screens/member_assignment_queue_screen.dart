import 'package:flutter/material.dart';

import '../controllers/member_assignment_controller.dart';

class MemberAssignmentQueueScreen extends StatelessWidget {
  const MemberAssignmentQueueScreen({
    required this.controller,
    super.key,
  });

  final MemberAssignmentController controller;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: controller,
      builder: (context, _) {
        if (controller.isLoading && controller.items.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        if (controller.errorMessage != null && controller.items.isEmpty) {
          return Center(child: Text(controller.errorMessage!));
        }

        return RefreshIndicator(
          onRefresh: controller.load,
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                'Pending approvals',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 16),
              if (controller.items.isEmpty)
                const Card(
                  child: Padding(
                    padding: EdgeInsets.all(18),
                    child: Text('No pending assignments right now.'),
                  ),
                )
              else
                ...controller.items.map((item) {
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Member #${item.memberId}',
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          const SizedBox(height: 8),
                          Text(item.reason ?? 'No reason supplied'),
                          const SizedBox(height: 12),
                          Text('Pending for ${item.pendingForSeconds}s'),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: () => controller.reject(item.id),
                                  child: const Text('Reject'),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: FilledButton(
                                  onPressed: () => controller.approve(item.id),
                                  child: const Text('Approve'),
                                ),
                              ),
                            ],
                          ),
                        ],
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
