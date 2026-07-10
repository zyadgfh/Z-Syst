import 'package:flutter/material.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../../../constant.dart';

class DateRangePicker extends StatefulWidget {
  final Function(DateTime? fromDate, DateTime? toDate, String? status) onDateRangeSelected;
  final PagingController controller;
  final DateTime? fromDate;
  final DateTime? toDate;
  final String? status;
  final bool showOnlyDatePicker;

  const DateRangePicker({
    super.key,
    required this.onDateRangeSelected,
    required this.controller,
    this.fromDate,
    this.toDate,
    this.status,
    required this.showOnlyDatePicker,
  });

  @override
  _DateRangePickerState createState() => _DateRangePickerState();
}

class _DateRangePickerState extends State<DateRangePicker> {
  TextEditingController fromDateController = TextEditingController();
  TextEditingController toDateController = TextEditingController();

  DateTime? _fromDate;
  DateTime? _toDate;
  String? _status;
  String? _errorMessage;
  bool _isFiltered = false;

  @override
  void initState() {
    super.initState();

    _fromDate = widget.fromDate;
    _toDate = widget.toDate ?? DateTime.now();
    _status = widget.status;

    if (_fromDate != null) {
      fromDateController.text = _formatDate(_fromDate!);
    }
    if (_toDate != null) {
      toDateController.text = _formatDate(_toDate!);
    }

    if (_fromDate != null && _toDate != null) {
      _isFiltered = true;
    }
  }

  String _formatDate(DateTime date) {
    return DateFormat('yyyy-MM-dd').format(date);
  }

  Future<DateTime?> _selectDate(BuildContext context, {DateTime? initialDate}) async {
    DateTime firstDate = DateTime(2000);
    DateTime lastDate = DateTime(2101);

    return await showDatePicker(
      context: context,
      initialDate: initialDate ?? DateTime.now(),
      firstDate: firstDate,
      lastDate: lastDate,
    );
  }

  // --- From date
  Future<void> _selectFromDate(BuildContext context) async {
    DateTime? selectedDate = await _selectDate(context);
    if (selectedDate != null) {
      setState(() {
        _fromDate = selectedDate;
        fromDateController.text = _formatDate(selectedDate);
        _errorMessage = null;
        _isFiltered = true;
      });

      if (_toDate != null) {
        toDateController.text = _formatDate(_toDate!);
      }

      widget.onDateRangeSelected(_fromDate, _toDate, _status);
    }
  }

  // --- To date
  Future<void> _selectToDate(BuildContext context) async {
    // if (_fromDate == null) {
    //   setState(() {
    //     _errorMessage = "Please select a From Date first.";
    //   });
    //   return;
    // }

    DateTime? selectedDate = await _selectDate(context, initialDate: _fromDate);
    if (selectedDate != null) {
      if (selectedDate.isBefore(_fromDate!)) {
        setState(() {
          _errorMessage = "To Date cannot be before From Date.";
        });
      } else {
        setState(() {
          _toDate = selectedDate;
          toDateController.text = _formatDate(selectedDate);
          _errorMessage = null;
          _isFiltered = true;
        });
        widget.onDateRangeSelected(_fromDate, _toDate, _status);
      }
    }
  }

  // -----picker section
  Widget _buildDatePickers(BuildContext context) {
    final lang = l.S.of(context);
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: <Widget>[
        Expanded(
          child: GestureDetector(
            onTap: () => _selectFromDate(context),
            child: TextField(
              controller: fromDateController,
              enabled: false,
              decoration: InputDecoration(
                labelText: lang.fromDate,
                hintText: lang.selectFromDate,
                suffixIcon: Icon(Icons.calendar_month_rounded),
              ),
            ),
          ),
        ),
        SizedBox(width: 16),
        Expanded(
          child: GestureDetector(
            onTap: () => _selectToDate(context),
            child: TextField(
              controller: toDateController,
              enabled: false,
              decoration: InputDecoration(
                labelText: lang.toDate,
                hintText: lang.selectToDate,
                suffixIcon: Icon(Icons.calendar_month_rounded),
              ),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final _theme = Theme.of(context);
    final lang = l.S.of(context);
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(lang.filter, style: _theme.textTheme.titleMedium),
              CloseButton(
                onPressed: () => Navigator.pop(context),
              ),
            ],
          ),
        ),
        Divider(
          thickness: 0.4,
          color: kBorderColorTextField,
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 24),
          child: Column(
            children: [
              _buildDatePickers(context),
              if (_errorMessage != null) ...[
                SizedBox(height: 8),
                Text(
                  _errorMessage!,
                  style: TextStyle(color: Colors.red, fontSize: 12),
                ),
              ],

              if (widget.showOnlyDatePicker == false) SizedBox(height: 24),

              // Select Status
              if (widget.showOnlyDatePicker == false)
                DropdownButtonFormField<String?>(
                  hint: Text(lang.selectType),
                  decoration: InputDecoration(labelText: lang.paymentStatus),
                  value: _status,
                  items: [
                    "Paid",
                    "Unpaid",
                  ]
                      .map((type) => DropdownMenuItem<String?>(
                            value: type,
                            child: Text(
                              type,
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ))
                      .toList(),
                  onChanged: (value) {
                    setState(() {
                      _status = value;
                      _isFiltered = true;
                    });
                    widget.onDateRangeSelected(_fromDate, _toDate, _status); // Notify with the selected dates and status
                  },
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return lang.pleaseSelectPaymentType;
                    }
                    return null;
                  },
                ),
              SizedBox(height: 24),

              // -----------submit buttons
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Color(0xffFFE2E2),
                      ),
                      onPressed: () {
                        if (_isFiltered) {
                          widget.onDateRangeSelected(null, null, null);
                          widget.controller.refresh();
                        }
                        Navigator.pop(context);
                      },
                      child: Text(
                        _isFiltered ? lang.clear : lang.cancel,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Color(0xffF44236)),
                      ),
                    ),
                  ),
                  SizedBox(width: 21),
                  Expanded(
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: kMainColor,
                      ),
                      onPressed: () {
                        if (_isFiltered) {
                          widget.onDateRangeSelected(_fromDate, _toDate, _status);
                          widget.controller.refresh();
                        }
                        Navigator.pop(context);
                      },
                      child: Text(lang.apply),
                    ),
                  ),
                ],
              ),
            ],
          ),
        )
      ],
    );
  }
}
