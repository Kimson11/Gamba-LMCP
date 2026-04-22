import 'package:flutter/material.dart';

import '../controllers/member_home_controller.dart';

class MemberHomeScreen extends StatefulWidget {
  const MemberHomeScreen({
    required this.controller,
    super.key,
  });

  final MemberHomeController controller;

  @override
  State<MemberHomeScreen> createState() => _MemberHomeScreenState();
}

class _MemberHomeScreenState extends State<MemberHomeScreen> {
  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.controller,
      builder: (context, _) {
        if (widget.controller.isLoading && widget.controller.summary == null) {
          return const Center(child: CircularProgressIndicator());
        }

        if (widget.controller.errorMessage != null && widget.controller.summary == null) {
          return Center(child: Text(widget.controller.errorMessage!));
        }

        final summary = widget.controller.summary;

        if (summary == null) {
          return const Center(child: Text('No member summary available.'));
        }

        return RefreshIndicator(
          onRefresh: widget.controller.load,
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                summary.memberName,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                summary.memberNumber,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 20),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _MetricCard(
                    title: 'Today',
                    value: '${summary.todayQuantityLiters.toStringAsFixed(1)} L',
                    icon: Icons.water_drop,
                  ),
                  _MetricCard(
                    title: '7 days',
                    value: '${summary.sevenDayTotalLiters.toStringAsFixed(1)} L',
                    icon: Icons.analytics_outlined,
                  ),
                  _MetricCard(
                    title: 'Pending sync',
                    value: summary.pendingSyncItems.toString(),
                    icon: Icons.sync_problem_outlined,
                  ),
                  _MetricCard(
                    title: 'Alerts',
                    value: summary.unreadNotifications.toString(),
                    icon: Icons.notifications_active_outlined,
                  ),
                ],
              ),
              const SizedBox(height: 24),
              Text(
                'Recent activity',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 12),
              if (summary.recentActivity.isEmpty)
                const Card(
                  child: Padding(
                    padding: EdgeInsets.all(18),
                    child: Text('Your recent milk activity will appear here.'),
                  ),
                )
              else
                ...summary.recentActivity.map((activity) {
                  return Card(
                    child: ListTile(
                      leading: const CircleAvatar(
                        child: Icon(Icons.water_drop_outlined),
                      ),
                      title: Text('${activity.quantityLiters.toStringAsFixed(1)} liters'),
                      subtitle: Text(activity.productionDate),
                      trailing: Text(activity.status),
                    ),
                  );
                }),
              const SizedBox(height: 16),
              if (summary.lastProcessedAt != null)
                Text(
                  'Last sync update: ${summary.lastProcessedAt}',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
            ],
          ),
        );
      },
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
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
      width: 160,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon),
              const SizedBox(height: 14),
              Text(title, style: Theme.of(context).textTheme.bodyMedium),
              const SizedBox(height: 4),
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
