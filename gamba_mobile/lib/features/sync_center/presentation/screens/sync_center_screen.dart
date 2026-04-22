import 'package:flutter/material.dart';

import '../../data/sync_repository_impl.dart';
import '../controllers/sync_center_controller.dart';

class SyncCenterScreen extends StatefulWidget {
  const SyncCenterScreen({
    required this.controller,
    super.key,
  });

  final SyncCenterController controller;

  @override
  State<SyncCenterScreen> createState() => _SyncCenterScreenState();
}

class _SyncCenterScreenState extends State<SyncCenterScreen> {
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
          return const Center(child: Text('No sync data available.'));
        }

        return RefreshIndicator(
          onRefresh: widget.controller.load,
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                'Sync Center',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 20),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: summary.counts.entries.map((entry) {
                  return SizedBox(
                    width: 140,
                    child: Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(entry.key),
                            const SizedBox(height: 8),
                            Text(
                              entry.value.toString(),
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 24),
              Text(
                'Unresolved conflicts',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 12),
              if (summary.conflicts.isEmpty)
                const Card(
                  child: Padding(
                    padding: EdgeInsets.all(18),
                    child: Text('No unresolved conflicts.'),
                  ),
                )
              else
                ...summary.conflicts.map((conflict) => _ConflictCard(
                      item: conflict,
                      onResolve: (action) => widget.controller.resolveConflict(conflict, action: action),
                    )),
              const SizedBox(height: 24),
              if (widget.controller.pendingQueue.isNotEmpty) ...[
                Text(
                  'Queued offline resolutions',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  'These will sync automatically when connectivity is restored.',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                const SizedBox(height: 12),
                ...widget.controller.pendingQueue.map((queued) {
                  return Card(
                    color: Theme.of(context).colorScheme.surfaceVariant,
                    child: ListTile(
                      leading: const Icon(Icons.schedule),
                      title: Text('Conflict #${queued.conflictId}'),
                      subtitle: Text('Action: ${queued.action}'),
                      trailing: Text(
                        queued.status,
                        style: Theme.of(context).textTheme.labelSmall,
                      ),
                    ),
                  );
                }),
                const SizedBox(height: 24),
              ],
              const SizedBox(height: 24),
              Text(
                'Recent results',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 12),
              ...summary.recentResults.map((item) {
                return Card(
                  child: ListTile(
                    title: Text(item.requestType),
                    subtitle: Text(item.processedAt ?? 'Pending processing'),
                    trailing: Text(item.status),
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

class _ConflictCard extends StatelessWidget {
  const _ConflictCard({
    required this.item,
    required this.onResolve,
  });

  final SyncConflictItem item;
  final void Function(String action) onResolve;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.warning_amber_rounded, size: 18),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    item.conflictCode,
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text('Type: ${item.requestType}', style: Theme.of(context).textTheme.bodySmall),
            const SizedBox(height: 12),
            if (item.resolutionRequired)
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  OutlinedButton(
                    onPressed: () => onResolve('keep_local'),
                    child: const Text('Keep local'),
                  ),
                  const SizedBox(width: 8),
                  FilledButton(
                    onPressed: () => onResolve('view_server_record'),
                    child: const Text('View server record'),
                  ),
                ],
              )
            else
              Align(
                alignment: Alignment.centerRight,
                child: Text('Closed', style: Theme.of(context).textTheme.labelSmall),
              ),
          ],
        ),
      ),
    );
  }
}
