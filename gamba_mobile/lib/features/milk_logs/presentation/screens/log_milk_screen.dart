import 'package:flutter/material.dart';

import '../controllers/log_milk_controller.dart';

class LogMilkScreen extends StatefulWidget {
  const LogMilkScreen({
    required this.controller,
    required this.onLogged,
    super.key,
  });

  final LogMilkController controller;
  final VoidCallback onLogged;

  @override
  State<LogMilkScreen> createState() => _LogMilkScreenState();
}

class _LogMilkScreenState extends State<LogMilkScreen> {
  final _formKey = GlobalKey<FormState>();
  final _quantityController = TextEditingController();
  DateTime _selectedDate = DateTime.now();

  @override
  void dispose() {
    _quantityController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }

    final quantity = double.parse(_quantityController.text.trim());
    final success = await widget.controller.submit(
      quantityLiters: quantity,
      productionDate: _selectedDate,
    );

    if (success) {
      _quantityController.clear();
      widget.onLogged();
    }
  }

  Future<void> _pickDate(BuildContext context) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now(),
    );

    if (picked != null) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.controller,
      builder: (context, _) {
        return ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text(
              'Log milk',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              'Capture today’s production with an authoritative server receipt.',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 20),
            if (widget.controller.errorMessage != null) ...[
              Card(
                color: const Color(0xFFFFECE8),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text(widget.controller.errorMessage!),
                ),
              ),
              const SizedBox(height: 16),
            ],
            if (widget.controller.successMessage != null) ...[
              Card(
                color: const Color(0xFFE6F7E9),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text(widget.controller.successMessage!),
                ),
              ),
              const SizedBox(height: 16),
            ],
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      TextFormField(
                        controller: _quantityController,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        decoration: const InputDecoration(
                          labelText: 'Quantity in liters',
                        ),
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Enter the quantity.';
                          }

                          final quantity = double.tryParse(value.trim());

                          if (quantity == null || quantity <= 0) {
                            return 'Enter a valid quantity.';
                          }

                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      Text('Production date', style: Theme.of(context).textTheme.bodyMedium),
                      const SizedBox(height: 8),
                      OutlinedButton.icon(
                        onPressed: () => _pickDate(context),
                        icon: const Icon(Icons.event_outlined),
                        label: Text(_selectedDate.toIso8601String().split('T').first),
                      ),
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(
                          onPressed: widget.controller.isSubmitting ? null : _submit,
                          child: widget.controller.isSubmitting
                              ? const SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2),
                                )
                              : const Text('Submit milk log'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}
