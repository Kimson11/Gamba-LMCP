import 'package:flutter/material.dart';

import '../controllers/admin_dashboard_controller.dart';

class AdminDashboardScreen extends StatelessWidget {
  const AdminDashboardScreen({
    required this.controller,
    super.key,
  });

  final AdminDashboardController controller;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: controller,
      builder: (context, _) {
        if (controller.isLoading && controller.summary == null) {
          return const Center(child: CircularProgressIndicator());
        }

        if (controller.errorMessage != null && controller.summary == null) {
          return Center(child: Text(controller.errorMessage!));
        }

        final summary = controller.summary;

        if (summary == null) {
          return const Center(child: Text('No dashboard summary available.'));
        }

        return RefreshIndicator(
          onRefresh: controller.load,
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                'Admin dashboard',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 20),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _DashboardCard(
                    title: 'Active members',
                    value: summary.activeMembers.toString(),
                    icon: Icons.groups_2_outlined,
                  ),
                  _DashboardCard(
                    title: 'Today milk',
                    value: '${summary.todayMilkVolumeLiters.toStringAsFixed(1)} L',
                    icon: Icons.water_drop_outlined,
                  ),
                  _DashboardCard(
                    title: 'Pending approvals',
                    value: summary.pendingApprovals.toString(),
                    icon: Icons.approval_outlined,
                  ),
                  _DashboardCard(
                    title: 'Sync conflicts',
                    value: summary.unresolvedConflicts.toString(),
                    icon: Icons.error_outline,
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}

class _DashboardCard extends StatelessWidget {
  const _DashboardCard({
    required this.title,
    required this.value,
    required this.icon,
  });

  final String title;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 170,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon),
              const SizedBox(height: 14),
              Text(title),
              const SizedBox(height: 8),
              Text(
                value,
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
