class MemberHomeSummary {
  const MemberHomeSummary({
    required this.memberName,
    required this.memberNumber,
    required this.todayQuantityLiters,
    required this.sevenDayTotalLiters,
    required this.pendingSyncItems,
    required this.unreadNotifications,
    required this.recentActivity,
    required this.lastProcessedAt,
  });

  final String memberName;
  final String memberNumber;
  final double todayQuantityLiters;
  final double sevenDayTotalLiters;
  final int pendingSyncItems;
  final int unreadNotifications;
  final List<MemberActivityItem> recentActivity;
  final String? lastProcessedAt;

  factory MemberHomeSummary.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    final member = data['member'] as Map<String, dynamic>;
    final kpis = data['kpis'] as Map<String, dynamic>;
    final syncSummary = data['sync_summary'] as Map<String, dynamic>;

    return MemberHomeSummary(
      memberName: '${member['first_name']} ${member['last_name']}',
      memberNumber: member['member_number'] as String,
      todayQuantityLiters: (kpis['today_quantity_liters'] as num?)?.toDouble() ?? 0,
      sevenDayTotalLiters: (kpis['seven_day_total_quantity_liters'] as num?)?.toDouble() ?? 0,
      pendingSyncItems: kpis['pending_sync_items'] as int? ?? 0,
      unreadNotifications: kpis['unread_notifications'] as int? ?? 0,
      recentActivity: ((data['recent_activity'] as List<dynamic>?) ?? const [])
          .map((item) => MemberActivityItem.fromJson(item as Map<String, dynamic>))
          .toList(),
      lastProcessedAt: syncSummary['last_processed_at'] as String?,
    );
  }
}

class MemberActivityItem {
  const MemberActivityItem({
    required this.id,
    required this.quantityLiters,
    required this.productionDate,
    required this.status,
  });

  final int id;
  final double quantityLiters;
  final String productionDate;
  final String status;

  factory MemberActivityItem.fromJson(Map<String, dynamic> json) {
    return MemberActivityItem(
      id: json['id'] as int,
      quantityLiters: (json['quantity_liters'] as num?)?.toDouble() ?? 0,
      productionDate: json['production_date'] as String? ?? '',
      status: json['status'] as String? ?? 'unknown',
    );
  }
}
