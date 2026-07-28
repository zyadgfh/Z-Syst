import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:skeletonizer/skeletonizer.dart';
import '../../../constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../Model/product_model.dart';

typedef LabelProvider<T> = String Function(T item);
typedef ScreenBuilder = Widget Function();

class GenericDropdown<T> extends StatefulWidget {
  final List<T> dataAsyncValue;
  final String label;
  final String hint;
  final LabelProvider<T> labelProvider;
  final ScreenBuilder addNewScreenBuilder;
  final ValueChanged<T?> onChanged;
  final T? selectedValue;
  final String? Function(T?)? validator;
  final ValueChanged<T?>? onNewItemAdded;
  final GestureTapCallback? selectedCancelFunction;
  final WidgetRef ref;
  final bool? fromAddAction;
  final bool? showAddButton;
  final bool? isEnabled;
  final bool? clearSelectedButton;
  // final FutureProvider provider;

  const GenericDropdown({
    super.key,
    required this.dataAsyncValue,
    required this.label,
    required this.hint,
    required this.labelProvider,
    required this.addNewScreenBuilder,
    required this.onChanged,
    this.selectedCancelFunction,
    this.selectedValue,
    this.clearSelectedButton,
    this.validator,
    this.onNewItemAdded,
    required this.ref,
    this.fromAddAction,
    this.showAddButton,
    this.isEnabled,
    // required this.provider,
  });

  @override
  State<GenericDropdown<T>> createState() => _GenericDropdownState<T>();
}

class _GenericDropdownState<T> extends State<GenericDropdown<T>> {
  @override
  void initState() {
    // TODO: implement initState
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    T? addedData;
    return DropdownButtonFormField<T>(
      dropdownColor: Colors.white,
      isExpanded: true,
      borderRadius: BorderRadius.circular(10),
      icon: (widget.clearSelectedButton ?? false)
          ? GestureDetector(
              onTap: widget.selectedCancelFunction,
              child: Padding(
                padding: const EdgeInsets.all(4.0),
                child: Icon(
                  Icons.close,
                  size: 16,
                  color: Colors.red,
                ),
              ),
            )
          : Icon(Icons.keyboard_arrow_down_rounded),
      hint: Text(
        widget.hint,
        style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: kNutral700),
      ),
      decoration: InputDecoration(
        label: getFieldLabelText(context: context, label: widget.label),
        errorBorder: OutlineInputBorder(
          borderSide: BorderSide(color: Colors.red.shade800), // Error border
        ),
        // border: UnderlineInputBorder(),
        enabledBorder: (widget.fromAddAction ?? false) ? UnderlineInputBorder() : null,
        disabledBorder: (widget.fromAddAction ?? false) ? UnderlineInputBorder() : null,
        border: (widget.fromAddAction ?? false) ? UnderlineInputBorder() : null,
      ),
      initialValue: widget.selectedValue,
      validator: widget.validator,
      items: (widget.showAddButton == false)
          ? [
              ...widget.dataAsyncValue.map((item) {
                return DropdownMenuItem<T>(
                  value: item,
                  child: SizedBox(
                    child: Text(
                      widget.labelProvider(item),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: kTitleColor),
                    ),
                  ),
                );
              }),
            ]
          : [
              DropdownMenuItem<T>(
                enabled: false,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          widget.label,
                          style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: kTitleColor),
                        ),
                        Flexible(
                          child: GestureDetector(
                            onTap: () async {
                              addedData = await Navigator.push(
                                context,
                                MaterialPageRoute(builder: (context) => widget.addNewScreenBuilder()),
                              );
                              if (addedData != null) {
                                widget.onNewItemAdded!(addedData);
                                Navigator.pop(context);
                              }
                            },
                            child: Text(
                              '+ ${l.S.of(context).add}',
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    fontWeight: FontWeight.w500,
                                    color: kMainColor,
                                  ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    const Divider(thickness: 1.0, color: kOutlineColor, height: 1),
                  ],
                ),
              ),
              ...widget.dataAsyncValue.map((item) {
                return DropdownMenuItem<T>(
                  enabled: widget.isEnabled ?? true,
                  value: item,
                  child: SizedBox(
                    child: Text(
                      widget.labelProvider(item),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: kTitleColor),
                    ),
                  ),
                );
              }),
            ],
      onChanged: widget.onChanged,
    );
  }
}

class DropdownSkeletonWidget extends StatelessWidget {
  const DropdownSkeletonWidget({super.key});

  @override
  Widget build(BuildContext context) {
    final lang = l.S.of(context);
    return Skeletonizer(
      enabled: true,
      child: DropdownButtonFormField(
        borderRadius: BorderRadius.circular(10),
        icon: const Icon(Icons.keyboard_arrow_down),
        hint: Text(lang.selectOne),
        decoration: InputDecoration(
          labelText: '${lang.loading}...',
        ),
        items: [],
        onChanged: (value) {},
      ),
    );
  }
}

Widget getFieldLabelText({required String label, required BuildContext context}) {
  final theme = Theme.of(context);
  return label.contains('*')
      ? RichText(
          text: TextSpan(text: label.replaceAll('*', ''), style: theme.textTheme.bodyLarge, children: [
          TextSpan(
              text: '*',
              style: theme.textTheme.bodyLarge?.copyWith(
                color: kSubTitleColor,
              ))
        ]))
      : Text(label);
}

class SearchableProductDropdown extends StatelessWidget {
  final List<ProductModel> products;
  final ValueChanged<ProductModel?> onProductSelected;

  const SearchableProductDropdown({
    super.key,
    required this.products,
    required this.onProductSelected,
  });

  @override
  Widget build(BuildContext context) {
    return RawAutocomplete<ProductModel>(
      optionsBuilder: (TextEditingValue textEditingValue) {
        return products.where((product) {
          return product.productName!.toLowerCase().contains(textEditingValue.text.toLowerCase());
        }).toList();
      },
      onSelected: (ProductModel selectedProduct) {
        onProductSelected(selectedProduct);
      },
      fieldViewBuilder: (
        BuildContext context,
        TextEditingController textEditingController,
        FocusNode focusNode,
        VoidCallback onFieldSubmitted,
      ) {
        return TextFormField(
          controller: textEditingController,
          decoration: InputDecoration(
              hintText: '${l.S.of(context).searchProduct}...',
              prefixIcon: const Icon(IconlyLight.search),
              enabledBorder: (UnderlineInputBorder(borderSide: BorderSide(color: kBorderColor))),
              disabledBorder: (UnderlineInputBorder(borderSide: BorderSide(color: kBorderColor))),
              border: (UnderlineInputBorder(borderSide: BorderSide(color: kBorderColor))),
              focusedBorder: (UnderlineInputBorder(borderSide: BorderSide(color: kBorderColor))),
              contentPadding: EdgeInsets.symmetric(vertical: 10, horizontal: 0)),
          focusNode: focusNode,
        );
      },
      optionsViewBuilder: (
        BuildContext context,
        AutocompleteOnSelected<ProductModel> onSelected,
        Iterable<ProductModel> options,
      ) {
        return Material(
          color: Colors.white,
          elevation: 4.0,
          child: ListView.builder(
            shrinkWrap: true,
            itemCount: options.length,
            physics: AlwaysScrollableScrollPhysics(),
            itemBuilder: (BuildContext context, int index) {
              final ProductModel option = options.elementAt(index);
              return ListTile(
                onTap: () => onSelected(option),
                title: Text(option.productName ?? l.S.of(context).unknownProduct),
              );
            },
          ),
        );
      },
    );
  }
}
